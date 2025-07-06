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
        case 'logout':
            session_destroy();
            header('Location: index.php');
            exit;
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

$series_id = $_GET['id'] ?? $_POST['series_id'] ?? null;
if (!$series_id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM series WHERE id = ?');
$stmt->execute([$series_id]);
$series = $stmt->fetch();
if (!$series) {
    echo 'Series not found';
    exit;
}

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
<title><?= htmlspecialchars($series['title']) ?></title>
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
<?php if ($message): ?>
<div class="alert alert-warning"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<div class="card mb-3">
  <div class="card-header"><h2><?= htmlspecialchars($series['title']) ?></h2></div>
  <div class="card-body">
    <p><?= nl2br(htmlspecialchars($series['description'])) ?></p>
    <?php if ($user): ?>
    <h4>Edit Series</h4>
    <form method="post" class="mb-3">
        <input type="hidden" name="action" value="update_series">
        <input type="hidden" name="series_id" value="<?= $series['id'] ?>">
        <input name="title" value="<?= htmlspecialchars($series['title']) ?>" class="form-control mb-1">
        <textarea name="description" class="form-control mb-1"><?= htmlspecialchars($series['description']) ?></textarea>
        <button class="btn btn-primary">Save</button>
    </form>
    <h4>Add Episode</h4>
    <form method="post" class="mb-3">
        <input type="hidden" name="action" value="add_episode">
        <input type="hidden" name="series_id" value="<?= $series['id'] ?>">
        <input name="season" placeholder="Season" class="form-control mb-1">
        <input name="episode" placeholder="Episode" class="form-control mb-1">
        <input name="title" placeholder="Title" class="form-control mb-1">
        <button class="btn btn-success">Add Episode</button>
    </form>
    <?php endif; ?>
    <ul class="list-group">
    <?php foreach (episodes_for_series($series['id']) as $e): ?>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
