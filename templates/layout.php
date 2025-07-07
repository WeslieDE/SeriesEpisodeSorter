<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>
<link href="<?= $baseUrl ?>/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= $baseUrl ?>/style.css">
<link rel="manifest" href="<?= $baseUrl ?>/manifest.json">
<title><?= htmlspecialchars($title ?? $config['site_title']) ?></title>
</head>
<body class="container py-4">
<?php include_template('menu', compact('user', 'config')); ?>
<?php if (!empty($message)): ?>
<div class="alert alert-warning"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?= $content ?? '' ?>
<script src="<?= $baseUrl ?>/bootstrap.bundle.min.js"></script>
</body>
</html>
