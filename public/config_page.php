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

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'logout':
            session_destroy();
            header('Location: index.php');
            exit;
        case 'save_config':
            $configPath = __DIR__ . '/../config.php';
            if (!is_writable($configPath)) {
                $message = 'Config file is not writable.';
            } else {
                $data = [
                    'site_title' => $_POST['site_title'] ?? '',
                    'language' => $_POST['language'] ?? 'en',
                    'require_login' => !empty($_POST['require_login']),
                    'db' => [
                        'driver' => $_POST['db_driver'] ?? 'sqlite',
                        'sqlite' => $_POST['db_sqlite'] ?? '',
                        'mysql' => [
                            'host' => $_POST['db_mysql_host'] ?? 'localhost',
                            'dbname' => $_POST['db_mysql_dbname'] ?? 'series',
                            'user' => $_POST['db_mysql_user'] ?? 'user',
                            'pass' => $_POST['db_mysql_pass'] ?? 'pass',
                        ],
                    ],
                    'api_keys' => [],
                ];
                $apiJson = $_POST['api_keys_json'] ?? '';
                $apiData = $apiJson !== '' ? json_decode($apiJson, true) : [];
                if ($apiJson !== '' && ($apiData === null && json_last_error() !== JSON_ERROR_NONE)) {
                    $message = 'Invalid API keys JSON: ' . json_last_error_msg();
                    break;
                }
                $data['api_keys'] = $apiData;

                $php = "<?php\nreturn " . var_export($data, true) . ";\n";
                file_put_contents($configPath, $php);
                $config = $data;
                $message = 'Config saved.';
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<title><?= htmlspecialchars($config['site_title']) ?> - Config</title>
</head>
<body class="container py-4">
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-3">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php"><?= htmlspecialchars($config['site_title']) ?></a>
    <div class="d-flex ms-auto">
      <a class="btn btn-outline-secondary me-2" href="favorites.php">Favorites</a>
      <a class="btn btn-outline-secondary me-2" href="config_page.php">Config</a>
      <form method="post" class="d-flex">
        <input type="hidden" name="action" value="logout">
        <button class="btn btn-secondary">Logout</button>
      </form>
    </div>
  </div>
</nav>
<?php if ($message): ?>
<div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
