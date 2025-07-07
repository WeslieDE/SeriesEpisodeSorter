<form method="post">
  <input type="hidden" name="action" value="save_config">
  <h4>General</h4>
  <div class="mb-3">
    <label class="form-label">Site Title</label>
    <input name="site_title" class="form-control" value="<?= htmlspecialchars($config['site_title']) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Language</label>
    <input name="language" class="form-control" value="<?= htmlspecialchars($config['language']) ?>">
  </div>
  <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" id="require_login" name="require_login" value="1" <?= ($config['require_login'] ?? false) ? 'checked' : '' ?>>
    <label class="form-check-label" for="require_login">Require login for all pages</label>
  </div>
  <h4>Database</h4>
  <div class="mb-3">
    <label class="form-label">Driver</label>
    <select name="db_driver" class="form-select">
      <?php foreach(['sqlite','mysql'] as $d): ?>
      <option value="<?= $d ?>" <?= $config['db']['driver'] === $d ? 'selected' : '' ?>><?= ucfirst($d) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">SQLite Path</label>
    <input name="db_sqlite" class="form-control" value="<?= htmlspecialchars($config['db']['sqlite']) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">MySQL Host</label>
    <input name="db_mysql_host" class="form-control" value="<?= htmlspecialchars($config['db']['mysql']['host']) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">MySQL Database</label>
    <input name="db_mysql_dbname" class="form-control" value="<?= htmlspecialchars($config['db']['mysql']['dbname']) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">MySQL User</label>
    <input name="db_mysql_user" class="form-control" value="<?= htmlspecialchars($config['db']['mysql']['user']) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">MySQL Password</label>
    <input name="db_mysql_pass" class="form-control" value="<?= htmlspecialchars($config['db']['mysql']['pass']) ?>">
  </div>
  <h4>API Keys (JSON)</h4>
  <div class="mb-3">
    <textarea name="api_keys_json" rows="5" class="form-control"><?= htmlspecialchars(json_encode($config['api_keys'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></textarea>
  </div>
  <button class="btn btn-primary">Save</button>
</form>
