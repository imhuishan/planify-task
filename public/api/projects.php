<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'member';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create_project':
        if ($user_role !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Only admins can create projects']);
            break;
        }
        $name = $_POST['name'] ?? '';
        $desc = $_POST['description'] ?? '';
        $leader_id = $_POST['project_leader_id'] ?? null;

        if (!$name) {
            echo json_encode(['success' => false, 'error' => 'Project name is required']);
            break;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO projects (name, description, project_leader_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, $desc, $leader_id]);
            $projectId = $pdo->lastInsertId();

            // Auto-add leader as member
            if ($leader_id) {
                $pdo->prepare("INSERT IGNORE INTO project_members (project_id, user_id) VALUES (?, ?)")
                    ->execute([$projectId, $leader_id]);
                
                // Update user role to project_leader if they were a member
                $pdo->prepare("UPDATE users SET role = 'project_leader' WHERE id = ? AND role = 'member'")
                    ->execute([$leader_id]);
            }

            echo json_encode(['success' => true, 'id' => $projectId]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'list_projects':
        try {
            // Projects where user is leader OR member OR user is admin/manager (see all)
            if ($user_role === 'admin' || $user_role === 'manager') {
                $stmt = $pdo->query("SELECT p.*, u.username as leader_name FROM projects p LEFT JOIN users u ON p.project_leader_id = u.id ORDER BY p.created_at DESC");
            } else {
                $stmt = $pdo->prepare("
                    SELECT DISTINCT p.*, u.username as leader_name 
                    FROM projects p 
                    LEFT JOIN users u ON p.project_leader_id = u.id
                    LEFT JOIN project_members pm ON p.id = pm.project_id
                    WHERE p.project_leader_id = ? OR pm.user_id = ?
                    ORDER BY p.created_at DESC
                ");
                $stmt->execute([$user_id, $user_id]);
            }
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Determine if the current user can add members to each project
            foreach ($projects as &$project) {
                $project['can_add_members'] = ($user_role === 'admin' || $user_role === 'manager' || $project['project_leader_id'] == $user_id);
            }

            echo json_encode(['success' => true, 'projects' => $projects]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'add_member':
        $projectId = $_POST['project_id'] ?? null;
        $targetUserId = $_POST['user_id'] ?? null;

        if (!$projectId || !$targetUserId) {
            echo json_encode(['success' => false, 'error' => 'Missing data']);
            break;
        }

        try {
            // Permission check: admin, manager, or project leader of THIS project
            $stmt = $pdo->prepare("SELECT project_leader_id FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);
            $project = $stmt->fetch();

            if (!$project) {
                echo json_encode(['success' => false, 'error' => 'Project not found']);
                break;
            }

            if ($user_role !== 'admin' && $user_role !== 'manager' && $project['project_leader_id'] != $user_id) {
                echo json_encode(['success' => false, 'error' => 'Permission denied']);
                break;
            }

            $stmt = $pdo->prepare("INSERT IGNORE INTO project_members (project_id, user_id) VALUES (?, ?)");
            $stmt->execute([$projectId, $targetUserId]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'get_users':
        try {
            $stmt = $pdo->query("SELECT id, username, role FROM users ORDER BY username ASC");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'users' => $users]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
