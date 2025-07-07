<?php
$config = require __DIR__ . '/../config.php';

require_once __DIR__ . '/../src/DataAccess.php';
require_once __DIR__ . '/../src/Template.php';
$db = new DataAccess();

session_start();

function user_count(DataAccess $db) {
    return $db->userCount();
}

function current_user(DataAccess $db) {
    if (!empty($_SESSION['user_id'])) {
        return $db->getUserById((int)$_SESSION['user_id']);
    }
    return null;
}

$message = '';

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'register':
            if (user_count($db) > 0) {
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
            if ($u = current_user($db)) {
                if (!empty($_POST['title'])) {
                    $db->insertSeries($_POST['title'], $_POST['description'] ?? null);
                }
            }
            break;
    }
}

$user = current_user($db);
$user_count = user_count($db);
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
