<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/db.php';

$pdo = Database::getConnection();

if (!file_exists(__DIR__ . '/../data/app.db')) {
    if (!is_dir(__DIR__ . '/../data')) {
        mkdir(__DIR__ . '/../data', 0777, true);
    }
    require __DIR__ . '/../src/init_db.php';
}

session_start();

function user_count() {
    global $pdo;
    $stmt = $pdo->query('SELECT COUNT(*) as c FROM users');
    return (int)$stmt->fetch()['c'];
}

function current_user() {
    global $pdo;
    if (!empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    return null;
}

$message = '';

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'register':
            if (user_count() > 0) {
                $message = 'Registration disabled.';
                break;
            }
            if (!empty($_POST['username']) && !empty($_POST['password'])) {
                $stmt = $pdo->prepare('INSERT INTO users(username, password_hash) VALUES(?, ?)');
                $stmt->execute([
                    $_POST['username'],
                    password_hash($_POST['password'], PASSWORD_DEFAULT)
                ]);
                $message = 'Registered. You can login now.';
            }
            break;
        case 'login':
            if (!empty($_POST['username']) && !empty($_POST['password'])) {
                $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
                $stmt->execute([$_POST['username']]);
                $user = $stmt->fetch();
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
                    $stmt = $pdo->prepare('INSERT INTO series(title, description) VALUES(?, ?)');
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['description'] ?? null
                    ]);
                }
            }
            break;
        case 'add_episode':
            if ($u = current_user()) {
                if (!empty($_POST['series_id']) && !empty($_POST['title'])) {
                    $stmt = $pdo->prepare('INSERT INTO episodes(series_id, season, episode, title) VALUES(?, ?, ?, ?)');
                    $stmt->execute([
                        $_POST['series_id'],
                        $_POST['season'] ?? null,
                        $_POST['episode'] ?? null,
                        $_POST['title']
                    ]);
                }
            }
            break;
        case 'mark_watched':
            if ($u = current_user()) {
                if (!empty($_POST['episode_id'])) {
                    $stmt = $pdo->prepare('REPLACE INTO watched(user_id, episode_id, watched, rating, comment) VALUES(?, ?, 1, ?, ?)');
                    $stmt->execute([
                        $u['id'],
                        $_POST['episode_id'],
                        $_POST['rating'] ?? null,
                        $_POST['comment'] ?? null
                    ]);
                }
            }
            break;
    }
}

$user = current_user();

$series = $pdo->query('SELECT * FROM series ORDER BY id DESC')->fetchAll();
foreach ($series as &$s) {
    $stmt = $pdo->prepare(
        'SELECT e.*, IFNULL(w.watched,0) as watched, w.rating, w.comment
         FROM episodes e LEFT JOIN watched w
         ON e.id = w.episode_id AND w.user_id = ?
         WHERE e.series_id = ? ORDER BY season, episode'
    );
    $stmt->execute([$_SESSION['user_id'] ?? 0, $s['id']]);
    $s['episodes'] = $stmt->fetchAll();
}
unset($s);

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
$twig = new \Twig\Environment($loader);

echo $twig->render('index.html.twig', [
    'user' => $user,
    'user_count' => user_count(),
    'message' => $message,
    'series' => $series
]);
