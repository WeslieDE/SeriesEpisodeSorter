<?php

// expects $config, $db and helper functions from bootstrap

$user = current_user();
if (!$user) {
    header('Location: index.php');
    exit;
}

$message = '';
if (isset($_POST['action']) && $_POST['action'] === 'toggle_favorite' && !empty($_POST['episode_id'])) {
    $db->toggleFavorite($user['id'], (int)$_POST['episode_id']);
    header('Location: index.php?page=favorites');
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
