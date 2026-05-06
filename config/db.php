<?php
// Database Configuration
$server_name = getenv('DB_HOST') ?: "sql112.infinityfree.com";
$username = getenv('DB_USER') ?: "if0_41843107";
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "0zsJsDW1qJkPq";
$dbname = getenv('DB_NAME') ?: "if0_41843107_planify_task";
$port = getenv('DB_PORT') ?: "3306";

try {
    $pdo = new PDO("mysql:host=$server_name;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
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
    }
}

