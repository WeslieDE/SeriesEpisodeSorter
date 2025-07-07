<?php
$config = require __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/DataAccess.php';
$db = new DataAccess();

session_start();

function current_user(DataAccess $db) {
    if (!empty($_SESSION['user_id'])) {
        return $db->getUserById((int)$_SESSION['user_id']);
    }
    return null;
}

$user = current_user($db);
if (!$user) {
    header('Location: index.php');
    exit;
}

$message = '';
if (isset($_POST['action']) && $_POST['action'] === 'toggle_favorite' && !empty($_POST['episode_id'])) {
    $db->toggleFavorite($user['id'], (int)$_POST['episode_id']);
    header('Location: favorites.php');
    exit;
}

$favorites = $db->getFavorites($user['id']);
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
