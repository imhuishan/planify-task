<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($_POST['action'] === 'get_comments') {
            $task_id = $_POST['task_id'];
            $stmt = $pdo->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.task_id = ? ORDER BY c.created_at ASC");
            $stmt->execute([$task_id]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'comments' => $comments]);
            exit;
        }

        if ($_POST['action'] === 'add_comment') {
            $task_id = $_POST['task_id'];
            $comment_text = trim($_POST['comment']);

            if (empty($comment_text)) {
                echo json_encode(['success' => false, 'error' => 'Comment cannot be empty']);
                exit;
            }

            // Optional: verify the user has access to the task (assigned or created)
            $stmt = $pdo->prepare("SELECT id FROM tasks WHERE id = ? AND (assigned_to = ? OR created_by = ?)");
            $stmt->execute([$task_id, $user_id, $user_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'You do not have permission to comment on this task']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO comments (task_id, user_id, comment) VALUES (?, ?, ?)");
            $success = $stmt->execute([$task_id, $user_id, $comment_text]);
            echo json_encode(['success' => $success]);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
