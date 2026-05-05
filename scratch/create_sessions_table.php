<?php
require_once __DIR__ . '/../config/db.php';
$pdo->exec('CREATE TABLE IF NOT EXISTS sessions (id VARCHAR(128) NOT NULL PRIMARY KEY, data TEXT NOT NULL, timestamp INT UNSIGNED NOT NULL)');
echo 'Done';
