<?php

// expects $config, $db and helper functions from bootstrap

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
                        'driver' => 'sqlite',
                        'sqlite' => $_POST['db_sqlite'] ?? '',
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
$content = template('config', ['config' => $config]);

echo template('layout', [
    'config' => $config,
    'title' => $config['site_title'] . ' - Config',
    'user' => $user,
    'message' => $message,
    'content' => $content
]);
