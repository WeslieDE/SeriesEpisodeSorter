<?php
$config = require __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
$pdo = Database::getConnection();

session_start();

$series = $pdo->query('SELECT * FROM series ORDER BY id DESC')->fetchAll();

function episodes_for_series_public($series_id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT e.*, w.rating, w.comment FROM episodes e LEFT JOIN watched w ON e.id = w.episode_id AND w.user_id = 1 WHERE e.series_id = ? ORDER BY season, episode');
    $stmt->execute([$series_id]);
    return $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<title><?= htmlspecialchars($config['site_title']) ?> - Public</title>
</head>
<body class="container py-4">
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-3">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php"><?= htmlspecialchars($config['site_title']) ?></a>
    <div class="d-flex ms-auto">
      <a class="btn btn-outline-primary" href="index.php">Login</a>
    </div>
  </div>
</nav>
<h1>Watched Series</h1>
<?php foreach ($series as $s): ?>
<div class="card mb-3">
  <div class="card-header"><h3><?= htmlspecialchars($s['title']) ?></h3></div>
  <div class="card-body">
    <p><?= nl2br(htmlspecialchars($s['description'])) ?></p>
    <ul class="list-group">
      <?php foreach (episodes_for_series_public($s['id']) as $e): ?>
      <li class="list-group-item">
        <strong>S<?= $e['season'] ?>E<?= $e['episode'] ?>:</strong> <?= htmlspecialchars($e['title']) ?>
        <?php if ($e['comment']): ?>
        <div><em><?= htmlspecialchars($e['comment']) ?></em> (Rating: <?= htmlspecialchars($e['rating']) ?>)</div>
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
