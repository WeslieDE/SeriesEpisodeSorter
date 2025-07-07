<?php
$config = require __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
$pdo = Database::getConnection();

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

$user = current_user();
if (!$user) {
    header('Location: index.php');
    exit;
}

$message = '';
if (isset($_POST['action']) && $_POST['action'] === 'toggle_favorite' && !empty($_POST['episode_id'])) {
    $stmt = $pdo->prepare('SELECT favorite FROM watched WHERE user_id = ? AND episode_id = ?');
    $stmt->execute([$user['id'], $_POST['episode_id']]);
    $fav = $stmt->fetchColumn();
    if ($fav === false) {
        $ins = $pdo->prepare('INSERT INTO watched(user_id, episode_id, watched, rating, favorite) VALUES(?, ?, 0, NULL, 1)');
        $ins->execute([$user['id'], $_POST['episode_id']]);
    } else {
        $newFav = $fav ? 0 : 1;
        $upd = $pdo->prepare('UPDATE watched SET favorite = ? WHERE user_id = ? AND episode_id = ?');
        $upd->execute([$newFav, $user['id'], $_POST['episode_id']]);
    }
    header('Location: favorites.php');
    exit;
}

$stmt = $pdo->prepare('SELECT e.*, s.title AS series_title, w.rating FROM watched w JOIN episodes e ON w.episode_id = e.id JOIN series s ON e.series_id = s.id WHERE w.user_id = ? AND w.favorite = 1 ORDER BY w.rating');
$stmt->execute([$user['id']]);
$favorites = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<title><?= htmlspecialchars($config['site_title']) ?> - Favorites</title>
</head>
<body class="container py-4">
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-3">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php"><?= htmlspecialchars($config['site_title']) ?></a>
    <div class="d-flex ms-auto">
      <a class="btn btn-outline-secondary me-2" href="favorites.php">Favorites</a>
      <form method="post" action="index.php" class="d-flex">
        <input type="hidden" name="action" value="logout">
        <button class="btn btn-secondary">Logout</button>
      </form>
    </div>
  </div>
</nav>
<h2>Favorites</h2>
<table class="table table-sm episode-table">
  <colgroup>
    <col style="width:5%">
    <col style="width:25%">
    <col style="width:15%">
    <col style="width:55%">
  </colgroup>
  <thead>
    <tr><th></th><th>Series</th><th>Episode</th><th>Title</th></tr>
  </thead>
  <tbody>
  <?php foreach ($favorites as $f): ?>
    <tr data-id="<?= $f['id'] ?>">
      <td>
        <form method="post" action="favorites.php">
          <input type="hidden" name="action" value="toggle_favorite">
          <input type="hidden" name="episode_id" value="<?= $f['id'] ?>">
          <button class="btn btn-link p-0 border-0" style="color:gold">&#9733;</button>
        </form>
      </td>
      <td><a href="series.php?id=<?= $f['series_id'] ?>"><?= htmlspecialchars($f['series_title']) ?></a></td>
      <td><?= htmlspecialchars($f['season']) ?>x<?= htmlspecialchars($f['episode']) ?></td>
      <td><?= htmlspecialchars($f['title']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
var tb = document.querySelector('.episode-table tbody');
if (tb) {
  new Sortable(tb, {
    animation: 150,
    onEnd: function(){
      const order = Array.from(tb.children).map(tr => tr.dataset.id);
      fetch('reorder.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({order})
      });
    }
  });
}
</script>
</body>
</html>
