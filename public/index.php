<?php
$config = require __DIR__ . '/../config.php';

require_once __DIR__ . '/../src/DataAccess.php';
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
$login_required = ($config['require_login'] ?? false) && !$user && user_count($db) > 0;
$series = $login_required ? [] : $db->getAllSeries();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="manifest" href="/manifest.json">
<title><?= htmlspecialchars($config['site_title']) ?></title>
</head>
<body class="container py-4">
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-3">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php"><?= htmlspecialchars($config['site_title']) ?></a>
    <div class="d-flex ms-auto">
      <?php if ($user): ?>
      <a class="btn btn-outline-secondary me-2" href="favorites.php">Favorites</a>
      <a class="btn btn-outline-secondary me-2" href="config_page.php">Config</a>
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
<?php if (!$user && user_count($db) == 0): ?>
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
<?php if ($login_required): ?>
<div class="alert alert-info">Please log in to view this page.</div>
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
