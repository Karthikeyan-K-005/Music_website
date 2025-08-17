<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_login();

$user_id = $_SESSION['user']['id'];
$song_id = intval($_POST['song_id'] ?? 0);
$action  = $_POST['action'] ?? '';

if ($song_id <= 0) { http_response_code(400); echo "Invalid song."; exit; }

if ($action === 'add') {
    $stmt = $mysqli->prepare("INSERT IGNORE INTO favorites (user_id, song_id) VALUES (?,?)");
    $stmt->bind_param("ii", $user_id, $song_id);
    $ok = $stmt->execute();
    $stmt->close();
    echo $ok ? "added" : "error";
} elseif ($action === 'remove') {
    $stmt = $mysqli->prepare("DELETE FROM favorites WHERE user_id = ? AND song_id = ?");
    $stmt->bind_param("ii", $user_id, $song_id);
    $ok = $stmt->execute();
    $stmt->close();
    echo $ok ? "removed" : "error";
} else {
    http_response_code(400);
    echo "Invalid action.";
}
