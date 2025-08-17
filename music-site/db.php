<?php
// db.php
require_once __DIR__ . '/config.php';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) {
    http_response_code(500);
    die('Database connection failed: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');
