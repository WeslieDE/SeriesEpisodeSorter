<?php
$config = require __DIR__ . '/../config.php';

if ($config['db']['driver'] === 'sqlite') {
    $dbFile = $config['db']['sqlite'];
    if (!is_dir(dirname($dbFile))) {
        mkdir(dirname($dbFile), 0777, true);
    }
    $newDb = !file_exists($dbFile);
} else {
    $newDb = false;
}

require_once __DIR__ . '/../src/db.php';
$pdo = Database::getConnection();
if ($newDb) {
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
                $stmt->execute([$_POST['username'], password_hash($_POST['password'], PASSWORD_DEFAULT)]);
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
                    $stmt->execute([$_POST['title'], $_POST['description'] ?? null]);
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
        case 'update_series':
            if ($u = current_user()) {
                if (!empty($_POST['series_id']) && !empty($_POST['title'])) {
                    $stmt = $pdo->prepare('UPDATE series SET title = ?, description = ? WHERE id = ?');
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['description'] ?? null,
                        $_POST['series_id']
                    ]);
                }
            }
            break;
        case 'mark_watched':
            if ($u = current_user()) {
                if (!empty($_POST['episode_id'])) {
                    $stmt = $pdo->prepare('REPLACE INTO watched(user_id, episode_id, watched, rating, comment) VALUES(?, ?, 1, ?, ?)');
                    $stmt->execute([$u['id'], $_POST['episode_id'], $_POST['rating'] ?? null, $_POST['comment'] ?? null]);
                }
            }
            break;
        case 'mark_unwatched':
            if ($u = current_user()) {
                if (!empty($_POST['episode_id'])) {
                    $stmt = $pdo->prepare('DELETE FROM watched WHERE user_id = ? AND episode_id = ?');
                    $stmt->execute([$u['id'], $_POST['episode_id']]);
                }
            }
            break;
    }
}

$user = current_user();

$series = $pdo->query('SELECT * FROM series ORDER BY id DESC')->fetchAll();

function episodes_for_series_user($series_id, $uid) {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT e.*, IFNULL(w.watched,0) as watched, w.rating, w.comment
         FROM episodes e LEFT JOIN watched w
         ON e.id = w.episode_id AND w.user_id = ?
         WHERE e.series_id = ? ORDER BY season, episode"
    );
    $stmt->execute([$uid, $series_id]);
    return $stmt->fetchAll();
}

function episodes_for_series($series_id) {
    return episodes_for_series_user($series_id, $_SESSION['user_id'] ?? 0);
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="manifest" href="/manifest.json">
<title><?= htmlspecialchars($config['site_title']) ?></title>
</head>
<body class="container py-4">
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-3">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php"><?= htmlspecialchars($config['site_title']) ?></a>
    <div class="d-flex ms-auto">
      <a class="btn btn-outline-primary me-2" href="view.php">Public View</a>
      <?php if ($user): ?>
      <form method="post" class="d-flex">
        <input type="hidden" name="action" value="logout">
        <button class="btn btn-secondary">Logout</button>
      </form>
      <?php else: ?>
      <form method="post" class="d-flex">
        <input type="hidden" name="action" value="login">
        <input name="username" placeholder="Username" class="form-control me-1">
        <input name="password" type="password" placeholder="Password" class="form-control me-1">
        <button class="btn btn-primary">Login</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</nav>
<?php if (!$user && user_count() == 0): ?>
<div class="mb-3">
  <h2>Register</h2>
  <form method="post">
    <input type="hidden" name="action" value="register">
    <input name="username" placeholder="Username" class="form-control mb-1">
    <input name="password" type="password" placeholder="Password" class="form-control mb-1">
    <button class="btn btn-primary">Register</button>
  </form>
</div>
<?php endif; ?>
<?php if ($message): ?>
<div class="alert alert-warning"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($user): ?>
<h2>Add Series</h2>
<form method="post" class="mb-3">
    <input type="hidden" name="action" value="add_series">
    <input name="title" placeholder="Title" class="form-control mb-1">
    <textarea name="description" placeholder="Description" class="form-control mb-1"></textarea>
    <button class="btn btn-success">Add Series</button>
</form>
<?php endif; ?>
<?php foreach ($series as $s): ?>
<div class="card mb-3">
<div class="card-header"><h3><?= htmlspecialchars($s['title']) ?></h3></div>
<div class="card-body">
<p><?= nl2br(htmlspecialchars($s['description'])) ?></p>
<?php if ($user): ?>
<h4>Edit Series</h4>
<form method="post" class="mb-3">
    <input type="hidden" name="action" value="update_series">
    <input type="hidden" name="series_id" value="<?= $s['id'] ?>">
    <input name="title" value="<?= htmlspecialchars($s['title']) ?>" class="form-control mb-1">
    <textarea name="description" class="form-control mb-1"><?= htmlspecialchars($s['description']) ?></textarea>
    <button class="btn btn-primary">Save</button>
</form>
<h4>Add Episode</h4>
<form method="post" class="mb-3">
    <input type="hidden" name="action" value="add_episode">
    <input type="hidden" name="series_id" value="<?= $s['id'] ?>">
    <input name="season" placeholder="Season" class="form-control mb-1">
    <input name="episode" placeholder="Episode" class="form-control mb-1">
    <input name="title" placeholder="Title" class="form-control mb-1">
    <button class="btn btn-success">Add Episode</button>
</form>
<?php endif; ?>
<ul class="list-group">
<?php foreach (episodes_for_series($s['id']) as $e): ?>
<li class="list-group-item">
<strong>S<?= $e['season'] ?>E<?= $e['episode'] ?>:</strong> <?= htmlspecialchars($e['title']) ?>
<?php if ($user): ?>
    <?php if ($e['watched']): ?>
    <span class="badge bg-success">Watched</span>
    <form method="post" style="display:inline">
        <input type="hidden" name="action" value="mark_unwatched">
        <input type="hidden" name="episode_id" value="<?= $e['id'] ?>">
        <button class="btn btn-sm btn-warning">Mark Unwatched</button>
    </form>
    <?php else: ?>
    <form method="post" style="display:inline">
        <input type="hidden" name="action" value="mark_watched">
        <input type="hidden" name="episode_id" value="<?= $e['id'] ?>">
        <input name="rating" type="number" min="1" max="5" placeholder="Rating" style="width:80px">
        <input name="comment" placeholder="Comment">
        <button class="btn btn-sm btn-primary">Mark Watched</button>
    </form>
    <?php endif; ?>
    <?php if ($e['comment']): ?>
    <div><em><?= htmlspecialchars($e['comment']) ?></em> (Rating: <?= htmlspecialchars($e['rating']) ?>)</div>
    <?php endif; ?>
<?php endif; ?>
</li>
<?php endforeach; ?>
</ul>
</div>
</div>
<?php endforeach; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
