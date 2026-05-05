<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

$stmt = $pdo->query("
    SELECT a.*, u.username 
    FROM activities a 
    JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 10
");
$activities = $stmt->fetchAll();

echo json_encode(['success' => true, 'activities' => $activities]);
