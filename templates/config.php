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
    <label class="form-label">SQLite Path</label>
    <input name="db_sqlite" class="form-control" value="<?= htmlspecialchars($config['db']['sqlite']) ?>">
  </div>

  <h4>API Keys</h4>
  <div class="mb-3">
    <label class="form-label">IMDb (OMDb) API Key</label>
    <input name="omdb_api_key" class="form-control" value="<?= htmlspecialchars($config['api_keys']['omdb'] ?? '') ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Other API Keys (JSON)</label>
    <?php $otherKeys = $config['api_keys']; unset($otherKeys['omdb']); ?>
    <textarea name="api_keys_json" rows="5" class="form-control"><?= htmlspecialchars(json_encode($otherKeys, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></textarea>
  </div>
  <button class="btn btn-primary">Save</button>
</form>
