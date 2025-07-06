<?php
$config = require __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
$pdo = Database::getConnection();

session_start();
header('Content-Type: application/json');
$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$order = $input['order'] ?? [];
if (!is_array($order)) $order = [];

$upd = $pdo->prepare('UPDATE watched SET rating = ? WHERE user_id = ? AND episode_id = ?');
$ins = $pdo->prepare('INSERT INTO watched(user_id, episode_id, watched, rating, favorite) VALUES(?, ?, 0, ?, 0)');
foreach ($order as $idx => $eid) {
    $upd->execute([$idx + 1, $user_id, $eid]);
    if ($upd->rowCount() == 0) {
        $ins->execute([$user_id, $eid, $idx + 1]);
    }
}

echo json_encode(['status' => 'ok']);
?>
