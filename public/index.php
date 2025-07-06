<?php
$dbFile = __DIR__ . '/../data/app.db';
if (!is_dir(dirname($dbFile))) {
    mkdir(dirname($dbFile), 0777, true);
}
$newDb = !file_exists($dbFile);
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
    }
}

$user = current_user();

$series = $pdo->query('SELECT * FROM series ORDER BY id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="manifest" href="/manifest.json">
<title>Series Episode Sorter</title>
</head>
<body class="container py-4">
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-3">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">EpisodeSorter</a>
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
    <a class="btn btn-primary" href="series.php?id=<?= $s['id'] ?>">View Episodes</a>
  </div>
</div>
<?php endforeach; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
