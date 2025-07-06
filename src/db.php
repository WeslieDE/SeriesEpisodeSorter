<?php
class Database {
    private static $instance = null;
    public static function getConnection() {
        if (self::$instance === null) {
            $dsn = 'sqlite:' . __DIR__ . '/../data/app.db';
            $opts = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            self::$instance = new PDO($dsn, null, null, $opts);
        }
        return self::$instance;
    }
}
?>
