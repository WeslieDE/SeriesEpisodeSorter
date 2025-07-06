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
                $json = $_POST['config_json'] ?? '';
                $data = json_decode($json, true);
                if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                    $message = 'Invalid JSON: ' . json_last_error_msg();
                } else {
                    $php = "<?php\nreturn " . var_export($data, true) . ";\n";
                    file_put_contents($configPath, $php);
                    $config = $data;
                    $message = 'Config saved.';
                }
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
<title><?= htmlspecialchars($config['site_title']) ?> - Config</title>
</head>
<body class="container py-4">
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-3">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php"><?= htmlspecialchars($config['site_title']) ?></a>
    <div class="d-flex ms-auto">
      <a class="btn btn-outline-primary me-2" href="view.php">Public View</a>
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
    <textarea name="config_json" rows="20" class="form-control mb-2"><?= htmlspecialchars(json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></textarea>
    <button class="btn btn-primary">Save</button>
</form>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
