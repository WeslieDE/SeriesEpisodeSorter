<?php
$config = require __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/DataAccess.php';
require_once __DIR__ . '/../src/Template.php';
$db = new DataAccess();

session_start();

function current_user(DataAccess $db) {
    if (!empty($_SESSION['user_id'])) {
        return $db->getUserById((int)$_SESSION['user_id']);
    }
    return null;
}

$user = current_user($db);
if (!$user) {
    header('Location: index.php');
    exit;
}

$message = '';
if (isset($_POST['action']) && $_POST['action'] === 'toggle_favorite' && !empty($_POST['episode_id'])) {
    $db->toggleFavorite($user['id'], (int)$_POST['episode_id']);
    header('Location: favorites.php');
    exit;
}

$favorites = $db->getFavorites($user['id']);
$content = template('favorites', ['favorites' => $favorites]);

echo template('layout', [
    'config' => $config,
    'title' => $config['site_title'] . ' - Favorites',
    'user' => $user,
    'content' => $content
]);
