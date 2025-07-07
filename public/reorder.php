<?php
$config = require __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/DataAccess.php';
$db = new DataAccess();

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

$db->reorderEpisodes($user_id, $order);

echo json_encode(['status' => 'ok']);
?>
