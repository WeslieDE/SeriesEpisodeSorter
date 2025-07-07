<?php if (!$user && $user_count == 0): ?>
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
<?php if ($login_required): ?>
<div class="alert alert-info">Please log in to view this page.</div>
<?php endif; ?>
<?php if ($user): ?>
<h2>Add Series</h2>
<form method="post" class="mb-3" enctype="multipart/form-data">
    <input type="hidden" name="action" value="add_series">
    <input name="title" placeholder="Title" class="form-control mb-1">
    <textarea name="description" placeholder="Description" class="form-control mb-1"></textarea>
    <input type="file" name="cover" class="form-control mb-1">
    <button class="btn btn-success">Add Series</button>
</form>
<?php endif; ?>
<?php foreach ($series as $s): ?>
<div class="card mb-3">
  <div class="card-header"><h3><?= htmlspecialchars($s['title']) ?></h3></div>
  <?php if ($s['cover']): ?>
  <img src="<?= asset_url($s['cover']) ?>" class="card-img-top" loading="lazy" alt="cover">
  <?php endif; ?>
  <div class="card-body">
    <p><?= nl2br(htmlspecialchars($s['description'])) ?></p>
    <a class="btn btn-primary" href="index.php?page=series&id=<?= $s['id'] ?>">View Episodes</a>
  </div>
</div>
<?php endforeach; ?>
