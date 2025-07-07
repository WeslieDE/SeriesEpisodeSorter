<?php

// expects $config, $db and helper functions from bootstrap

$message = '';

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'register':
            if (user_count() > 0) {
                $message = 'Registration disabled.';
                break;
            }
            if (!empty($_POST['username']) && !empty($_POST['password'])) {
                $db->insertUser($_POST['username'], password_hash($_POST['password'], PASSWORD_DEFAULT));
                $message = 'Registered. You can login now.';
            }
            break;
        case 'login':
            if (!empty($_POST['username']) && !empty($_POST['password'])) {
                $user = $db->getUserByUsername($_POST['username']);
                if ($user && password_verify($_POST['password'], $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['id'];
                } else {
                    $message = 'Invalid credentials';
                }
            }
            break;
        case 'logout':
            session_destroy();
            header('Location: index.php');
            exit;
        case 'add_series':
            if ($u = current_user()) {
                if (!empty($_POST['title'])) {
                    $db->insertSeries($_POST['title'], $_POST['description'] ?? null);
                }
            }
            break;
    }
}

$user = current_user();
$user_count = user_count();
$login_required = ($config['require_login'] ?? false) && !$user && $user_count > 0;
$series = $login_required ? [] : $db->getAllSeries();
$content = template('index', [
    'user' => $user,
    'series' => $series,
    'login_required' => $login_required,
    'user_count' => $user_count
]);

echo template('layout', [
    'config' => $config,
    'title' => $config['site_title'],
    'user' => $user,
    'message' => $message,
    'content' => $content
]);
