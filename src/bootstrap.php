<?php
$config = require __DIR__ . '/../config.php';

require_once __DIR__ . '/DataAccess.php';
require_once __DIR__ . '/Template.php';

$db = new DataAccess();

session_start();

function user_count(): int {
    global $db;
    return $db->userCount();
}

function current_user() {
    global $db;
    if (!empty($_SESSION['user_id'])) {
        return $db->getUserById((int)$_SESSION['user_id']);
    }
    return null;
}

function asset_url(string $path): string {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    return $base . '/' . ltrim($path, '/');
}
