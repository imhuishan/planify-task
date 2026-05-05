<?php
// Database Configuration (Supports Vercel & Local)
$server_name = getenv('DB_HOST') ?: "192.168.100.43";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "huishan";
$dbname = getenv('DB_NAME') ?: "task_manager";
$port = getenv('DB_PORT') ?: "3306";

try {
    $pdo = new PDO("mysql:host=$server_name;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Set PHP and MySQL timezone to UTC+8
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

