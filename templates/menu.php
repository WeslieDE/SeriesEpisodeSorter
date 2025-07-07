<nav class="navbar navbar-expand-lg navbar-light bg-light mb-3">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php"><?= htmlspecialchars($config['site_title']) ?></a>
    <div class="d-flex ms-auto">
      <?php if (!empty($user)): ?>
      <a class="btn btn-outline-secondary me-2" href="favorites.php">Favorites</a>
      <a class="btn btn-outline-secondary me-2" href="config_page.php">Config</a>
      <form method="post" action="index.php" class="d-flex">
        <input type="hidden" name="action" value="logout">
        <button class="btn btn-secondary">Logout</button>
      </form>
      <?php else: ?>
      <form method="post" action="index.php" class="d-flex">
        <input type="hidden" name="action" value="login">
        <input name="username" placeholder="Username" class="form-control me-1">
        <input name="password" type="password" placeholder="Password" class="form-control me-1">
        <button class="btn btn-primary">Login</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</nav>
