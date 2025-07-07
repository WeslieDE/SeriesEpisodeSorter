<?php
require_once __DIR__ . '/db.php';

class DataAccess {
    private $pdo;

    public function __construct() {
        $config = require __DIR__ . '/../config.php';

        if ($config['db']['driver'] === 'sqlite') {
            $dbFile = $config['db']['sqlite'];
            if (!is_dir(dirname($dbFile))) {
                mkdir(dirname($dbFile), 0777, true);
            }
            $newDb = !file_exists($dbFile);
        } else {
            $newDb = false;
        }

        $this->pdo = Database::getConnection();

        if ($newDb) {
            require __DIR__ . '/init_db.php';
        }
    }

    // User related
    public function userCount(): int {
        $stmt = $this->pdo->query('SELECT COUNT(*) as c FROM users');
        return (int)$stmt->fetch()['c'];
    }

    public function getUserById(int $id) {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getUserByUsername(string $username) {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public function insertUser(string $username, string $passwordHash): void {
        $stmt = $this->pdo->prepare('INSERT INTO users(username, password_hash) VALUES(?, ?)');
        $stmt->execute([$username, $passwordHash]);
    }

    // Series related
    public function getAllSeries(): array {
        return $this->pdo->query('SELECT * FROM series ORDER BY id DESC')->fetchAll();
    }

    public function insertSeries(string $title, ?string $description): void {
        $stmt = $this->pdo->prepare('INSERT INTO series(title, description) VALUES(?, ?)');
        $stmt->execute([$title, $description]);
    }

    public function getSeriesById(int $id) {
        $stmt = $this->pdo->prepare('SELECT * FROM series WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateSeries(int $id, string $title, ?string $description): void {
        $stmt = $this->pdo->prepare('UPDATE series SET title = ?, description = ? WHERE id = ?');
        $stmt->execute([$title, $description, $id]);
    }

    // Episode related
    public function insertEpisode(int $seriesId, ?int $season, ?int $episode, string $title): void {
        $stmt = $this->pdo->prepare('INSERT INTO episodes(series_id, season, episode, title) VALUES(?, ?, ?, ?)');
        $stmt->execute([$seriesId, $season, $episode, $title]);
    }

    public function bulkAddEpisodes(int $seriesId, int $season, int $count): void {
        $stmt = $this->pdo->prepare('SELECT MAX(episode) FROM episodes WHERE series_id = ? AND season = ?');
        $stmt->execute([$seriesId, $season]);
        $start = (int)$stmt->fetchColumn() + 1;
        $ins = $this->pdo->prepare('INSERT INTO episodes(series_id, season, episode, title) VALUES(?, ?, ?, ?)');
        for ($i = 0; $i < $count; $i++) {
            $ins->execute([$seriesId, $season, $start + $i, '']);
        }
    }

    public function getEpisodesForSeriesUser(int $seriesId, int $uid): array {
        $stmt = $this->pdo->prepare(
            "SELECT e.*, IFNULL(w.watched,0) as watched, w.rating, w.comment, w.favorite
             FROM episodes e LEFT JOIN watched w
             ON e.id = w.episode_id AND w.user_id = ?
             WHERE e.series_id = ?
             ORDER BY e.season, CASE WHEN w.rating IS NOT NULL THEN w.rating ELSE e.episode END"
        );
        $stmt->execute([$uid, $seriesId]);
        return $stmt->fetchAll();
    }

    public function updateEpisodeTitle(int $episodeId, string $title): void {
        $stmt = $this->pdo->prepare('UPDATE episodes SET title = ? WHERE id = ?');
        $stmt->execute([$title, $episodeId]);
    }

    // Watched/favorites
    public function markWatched(int $userId, int $episodeId, ?string $comment): void {
        $check = $this->pdo->prepare('SELECT favorite, rating FROM watched WHERE user_id = ? AND episode_id = ?');
        $check->execute([$userId, $episodeId]);
        $row = $check->fetch();

        if ($row === false) {
            $ins = $this->pdo->prepare('INSERT INTO watched(user_id, episode_id, watched, rating, comment, favorite) VALUES(?, ?, 1, NULL, ?, 0)');
            $ins->execute([$userId, $episodeId, $comment]);
        } else {
            $upd = $this->pdo->prepare('UPDATE watched SET watched = 1, comment = ? WHERE user_id = ? AND episode_id = ?');
            $upd->execute([$comment, $userId, $episodeId]);
        }
    }

    public function markUnwatched(int $userId, int $episodeId): void {
        $stmt = $this->pdo->prepare('SELECT favorite FROM watched WHERE user_id = ? AND episode_id = ?');
        $stmt->execute([$userId, $episodeId]);
        $fav = $stmt->fetchColumn();

        if ($fav === false) {
            return; // nothing to update
        }

        $upd = $this->pdo->prepare('UPDATE watched SET watched = 0, comment = NULL WHERE user_id = ? AND episode_id = ?');
        $upd->execute([$userId, $episodeId]);
    }

    public function toggleFavorite(int $userId, int $episodeId): void {
        $stmt = $this->pdo->prepare('SELECT favorite FROM watched WHERE user_id = ? AND episode_id = ?');
        $stmt->execute([$userId, $episodeId]);
        $fav = $stmt->fetchColumn();
        if ($fav === false) {
            $ins = $this->pdo->prepare('INSERT INTO watched(user_id, episode_id, watched, rating, favorite) VALUES(?, ?, 0, NULL, 1)');
            $ins->execute([$userId, $episodeId]);
        } else {
            $newFav = $fav ? 0 : 1;
            $upd = $this->pdo->prepare('UPDATE watched SET favorite = ? WHERE user_id = ? AND episode_id = ?');
            $upd->execute([$newFav, $userId, $episodeId]);
        }
    }

    public function getFavorites(int $userId): array {
        $stmt = $this->pdo->prepare('SELECT e.*, s.title AS series_title, w.rating FROM watched w JOIN episodes e ON w.episode_id = e.id JOIN series s ON e.series_id = s.id WHERE w.user_id = ? AND w.favorite = 1 ORDER BY w.rating');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function reorderEpisodes(int $userId, array $order): void {
        $upd = $this->pdo->prepare('UPDATE watched SET rating = ? WHERE user_id = ? AND episode_id = ?');
        $ins = $this->pdo->prepare('INSERT INTO watched(user_id, episode_id, watched, rating, favorite) VALUES(?, ?, 0, ?, 0)');
        foreach ($order as $idx => $eid) {
            $upd->execute([$idx + 1, $userId, $eid]);
            if ($upd->rowCount() == 0) {
                $ins->execute([$userId, $eid, $idx + 1]);
            }
        }
    }
}
?>
