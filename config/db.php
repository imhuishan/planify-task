<?php
$server_name = getenv('DB_HOST') ?: "192.168.100.43";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "huishan";
$dbname = getenv('DB_NAME') ?: "task_manager";
$port = getenv('DB_PORT') ?: "3306";

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT => true
    ];
    $pdo = new PDO("mysql:host=$server_name;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
    
    date_default_timezone_set('Asia/Kuala_Lumpur');
    $pdo->exec("SET time_zone = '+08:00'");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

/**
 * Log a user activity
 * @param PDO $pdo
 * @param int $user_id
 * @param string $type
 * @param string $detail
 */
function logActivity($pdo, $user_id, $type, $detail) {
    try {
        $stmt = $pdo->prepare("INSERT INTO activities (user_id, activity_type, activity_detail) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $type, $detail]);
    } catch (Exception $e) {
        // Silently fail logging to prevent breaking main flow
    }
}

