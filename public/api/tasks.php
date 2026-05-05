<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'member';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'get_task') {
            $task_id = $_POST['id'];
            if ($user_role === 'admin' || $user_role === 'manager') {
                $stmt = $pdo->prepare("SELECT t.*, u.username as assigned_to_name, c.username as created_by_name, p.name as project_name FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id LEFT JOIN users c ON t.created_by = c.id LEFT JOIN projects p ON t.project_id = p.id WHERE t.id = ?");
                $stmt->execute([$task_id]);
            } else {
                $stmt = $pdo->prepare("SELECT t.*, u.username as assigned_to_name, c.username as created_by_name, p.name as project_name FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id LEFT JOIN users c ON t.created_by = c.id LEFT JOIN projects p ON t.project_id = p.id WHERE t.id = ? AND (t.assigned_to = ? OR t.created_by = ?)");
                $stmt->execute([$task_id, $user_id, $user_id]);
            }
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($task) {
                // Map status for frontend
                $map = ['pending' => 'waiting', 'in-progress' => 'doing', 'completed' => 'finish'];
                $task['status_label'] = $map[$task['status']] ?? $task['status'];
                echo json_encode(['success' => true, 'task' => $task]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Task not found']);
            }
            exit;
        }

        if ($_POST['action'] === 'edit_task') {
            $task_id = $_POST['id'];
            $title = trim($_POST['title']);
            $description = trim($_POST['description'] ?? '');
            $priority = $_POST['priority'] ?? 'medium';
            $category = $_POST['category'] ?? 'General';
            $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
            $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
            $project_id = !empty($_POST['project_id']) ? $_POST['project_id'] : null;

            $stmt = $pdo->prepare("UPDATE tasks SET title = ?, description = ?, priority = ?, category = ?, due_date = ?, deadline = ?, project_id = ? WHERE id = ? AND (assigned_to = ? OR created_by = ?)");
            $success = $stmt->execute([$title, $description, $priority, $category, $due_date, $deadline, $project_id, $task_id, $user_id, $user_id]);
            echo json_encode(['success' => $success]);
            exit;
        }

        if ($_POST['action'] === 'delete_task') {
            $task_id = $_POST['id'];
            // Only creator can delete
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND created_by = ?");
            $success = $stmt->execute([$task_id, $user_id]);
            echo json_encode(['success' => $success]);
            exit;
        }

        if ($_POST['action'] === 'update_date') {
            $task_id = $_POST['id'];
            $new_date = $_POST['due_date'];
            $stmt = $pdo->prepare("UPDATE tasks SET due_date = ? WHERE id = ? AND (assigned_to = ? OR created_by = ?)");
            $success = $stmt->execute([$new_date, $task_id, $user_id, $user_id]);
            echo json_encode(['success' => $success]);
            exit;
        }

        if ($_POST['action'] === 'update_status') {
            $task_id = $_POST['id'];
            $new_status = $_POST['status'];
            
            $map = [
                'waiting' => 'pending',
                'doing' => 'in-progress',
                'finish' => 'completed'
            ];
            $db_status = $map[$new_status] ?? $new_status;

            // BUG FIX: Only the assigned person can change the status
            $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND assigned_to = ?");
            $success = $stmt->execute([$db_status, $task_id, $user_id]);
            
            if ($success) {
                logActivity($pdo, $user_id, 'status_update', "Updated task #$task_id to " . ucfirst($new_status));
            }
            
            echo json_encode(['success' => $success]);
            exit;
        }

        if ($_POST['action'] === 'create_task') {
            $title = trim($_POST['title']);
            $description = trim($_POST['description'] ?? '');
            $priority = $_POST['priority'] ?? 'medium';
            $category = $_POST['category'] ?? 'General';
            $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
            $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
            $assigned_to = $_POST['assigned_to'] ?: $user_id;

            if (empty($title)) {
                echo json_encode(['success' => false, 'error' => 'Title is required']);
                exit;
            }

            $project_id = !empty($_POST['project_id']) ? $_POST['project_id'] : null;

            $stmt = $pdo->prepare("INSERT INTO tasks (title, description, assigned_to, created_by, status, priority, category, due_date, deadline, project_id) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?)");
            $success = $stmt->execute([$title, $description, $assigned_to, $user_id, $priority, $category, $due_date, $deadline, $project_id]);
            
            if ($success) {
                logActivity($pdo, $user_id, 'task_create', "Created a new task: $title");
            }
            
            echo json_encode(['success' => $success]);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// GET Tasks
try {
    $filter_status = $_GET['filter_status'] ?? 'all';
    $filter_priority = $_GET['filter_priority'] ?? 'all';
    $sort = $_GET['sort'] ?? 'created_desc';
    $project_id = $_GET['project_id'] ?? null;
    $view_user_id = $_GET['user_id'] ?? $user_id;

    if ($project_id) {
        // Check if user is member of project (or admin/manager)
        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $checkStmt = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ? UNION SELECT 1 FROM projects WHERE id = ? AND project_leader_id = ?");
            $checkStmt->execute([$project_id, $user_id, $project_id, $user_id]);
            if (!$checkStmt->fetch()) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied for this project']);
                exit;
            }
        }
        $query = "SELECT t.id, t.title, t.description, COALESCE(t.due_date, t.created_at) as start, t.status, t.priority, t.category, u.username as assigned_to_name 
                  FROM tasks t 
                  LEFT JOIN users u ON t.assigned_to = u.id 
                  WHERE t.project_id = :pid";
        $params = [':pid' => $project_id];
    } else {
        // Permission check: only admin/manager can view other users' tasks
        if ($view_user_id != $user_id && $user_role !== 'admin' && $user_role !== 'manager') {
            $view_user_id = $user_id; // Reset to self if unauthorized
        }

        $query = "SELECT t.id, t.title, t.description, COALESCE(t.due_date, t.created_at) as start, t.status, t.priority, t.category 
                  FROM tasks t 
                  WHERE t.assigned_to = :uid";
        $params = [':uid' => $view_user_id];
    }

    if ($filter_status !== 'all') {
        $map = ['waiting' => 'pending', 'doing' => 'in-progress', 'finish' => 'completed'];
        $db_status = $map[$filter_status] ?? $filter_status;
        $query .= " AND t.status = :status";
        $params[':status'] = $db_status;
    }

    if ($filter_priority !== 'all') {
        $query .= " AND t.priority = :priority";
        $params[':priority'] = $filter_priority;
    }

    if ($sort === 'due_asc') {
        $query .= " ORDER BY t.due_date ASC";
    } else {
        $query .= " ORDER BY t.created_at DESC";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tasks as &$task) {
        if ($task['status'] === 'completed' || $task['status'] === 'finish') {
            $task['status'] = 'finish';
            $task['color'] = '#22c55e';
        } elseif ($task['status'] === 'in-progress' || $task['status'] === 'doing') {
            $task['status'] = 'doing';
            $task['color'] = '#3b82f6';
        } else {
            $task['status'] = 'waiting';
            $task['color'] = '#94a3b8';
        }
    }

    header('Content-Type: application/json');
    echo json_encode($tasks);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
