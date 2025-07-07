<?php
require_once __DIR__ . '/db.php';
$pdo = Database::getConnection();
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE,
    password_hash TEXT NOT NULL,
    totp_secret TEXT
);");
$pdo->exec("CREATE TABLE IF NOT EXISTS series (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    cover TEXT,
    imdb_id TEXT
);");
$pdo->exec("CREATE TABLE IF NOT EXISTS episodes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    series_id INTEGER NOT NULL,
    season INTEGER,
    episode INTEGER,
    title TEXT,
    FOREIGN KEY(series_id) REFERENCES series(id)
);");
$pdo->exec("CREATE TABLE IF NOT EXISTS watched (
    user_id INTEGER,
    episode_id INTEGER,
    watched INTEGER DEFAULT 0,
    rating INTEGER,
    comment TEXT,
    favorite INTEGER DEFAULT 0,
    PRIMARY KEY(user_id, episode_id),
    FOREIGN KEY(user_id) REFERENCES users(id),
    FOREIGN KEY(episode_id) REFERENCES episodes(id)
);");
$pdo->exec("CREATE TABLE IF NOT EXISTS config (
    key TEXT PRIMARY KEY,
    value TEXT
);");
?>
