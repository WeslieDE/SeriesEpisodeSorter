<?php
class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config.php';

            switch ($config['db']['driver']) {
                case 'sqlite':
                    $dsn = 'sqlite:' . $config['db']['sqlite'];
                    self::$instance = new PDO($dsn, null, null, self::opts());
                    break;
                default:
                    throw new Exception('Unsupported DB driver');
            }
        }

        return self::$instance;
    }

    private static function opts(): array {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
    }
}
?>
