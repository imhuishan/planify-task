<?php
require_once __DIR__ . '/public/../config/db.php';
$stmt = $pdo->query("DESCRIBE users");
print_r($stmt->fetchAll());
