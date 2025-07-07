<?php

// expects $config, $db and helper functions from bootstrap
if (!isset($_SESSION['edit_mode'])) {
    $_SESSION['edit_mode'] = false;
}

$message = '';

function process_cover_upload(?array $file): ?string {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    if (!is_dir(__DIR__ . '/../public/covers')) {
        mkdir(__DIR__ . '/../public/covers', 0777, true);
    }
    $data = file_get_contents($file['tmp_name']);
    if ($data === false) {
        return null;
    }
    $im = @imagecreatefromstring($data);
    if (!$im) {
        return null;
    }
    $w = imagesx($im);
    $h = imagesy($im);
    $max = 1024;
    if ($w > $max || $h > $max) {
        $ratio = min($max / $w, $max / $h);
        $nw = (int)($w * $ratio);
        $nh = (int)($h * $ratio);
        $res = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($res, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($im);
        $im = $res;
    }
    $name = 'covers/' . uniqid('cover_', true) . '.jpg';
    imagejpeg($im, __DIR__ . '/../public/' . $name, 85);
    imagedestroy($im);
    return $name;
}

function download_cover(string $url): ?string {
    $data = @file_get_contents($url);
    if ($data === false) {
        return null;
    }
    $tmp = tmpfile();
    if (!$tmp) {
        return null;
    }
    $meta = stream_get_meta_data($tmp);
    file_put_contents($meta['uri'], $data);
    $name = process_cover_upload(['tmp_name' => $meta['uri'], 'error' => UPLOAD_ERR_OK]);
    fclose($tmp);
    return $name;
}

function import_from_imdb(int $seriesId, string $imdbId): string {
    global $db, $config;
    $apiKey = $config['api_keys']['omdb'] ?? '';
    if ($apiKey === '') {
        return 'OMDb API key missing in config.';
    }
    $base = 'http://www.omdbapi.com/?apikey=' . urlencode($apiKey) . '&i=' . urlencode($imdbId);
    $json = @file_get_contents($base);
    if ($json === false) {
        return 'Failed to fetch series info.';
    }
    $info = json_decode($json, true);
    if (!$info || ($info['Response'] ?? 'False') !== 'True') {
        return $info['Error'] ?? 'Invalid response from OMDb.';
    }

    // Update cover if provided
    if (!empty($info['Poster']) && strpos($info['Poster'], 'http') === 0) {
        if ($cover = download_cover($info['Poster'])) {
            $db->updateSeries($seriesId, $info['Title'] ?? '', $info['Plot'] ?? null, $cover, $imdbId);
        } else {
            $db->updateSeries($seriesId, $info['Title'] ?? '', $info['Plot'] ?? null, null, $imdbId);
        }
    } else {
        $db->updateSeries($seriesId, $info['Title'] ?? '', $info['Plot'] ?? null, null, $imdbId);
    }

    $db->deleteEpisodesForSeries($seriesId);
    for ($season = 1; ; $season++) {
        $seasonJson = @file_get_contents($base . '&Season=' . $season);
        $sinfo = $seasonJson ? json_decode($seasonJson, true) : null;
        if (!$sinfo || ($sinfo['Response'] ?? 'False') !== 'True') {
            break;
        }
        foreach ($sinfo['Episodes'] as $ep) {
            $db->insertEpisode($seriesId, $season, (int)$ep['Episode'], $ep['Title']);
        }
    }
    return 'Imported from IMDb.';
}

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'logout':
            session_destroy();
            header('Location: index.php');
            exit;
        case 'toggle_edit_mode':
            $_SESSION['edit_mode'] = !($_SESSION['edit_mode'] ?? false);
            break;
        case 'update_series':
            if ($u = current_user()) {
                if (!empty($_POST['series_id']) && !empty($_POST['title'])) {
                    $cover = process_cover_upload($_FILES['cover'] ?? null);
                    $db->updateSeries((int)$_POST['series_id'], $_POST['title'], $_POST['description'] ?? null, $cover, $_POST['imdb_id'] ?? null);
                }
            }
            break;
        case 'add_episode':
            if ($u = current_user()) {
                if (!empty($_POST['series_id'])) {
                    $db->insertEpisode((int)$_POST['series_id'], $_POST['season'] !== '' ? (int)$_POST['season'] : null, $_POST['episode'] !== '' ? (int)$_POST['episode'] : null, $_POST['title'] ?? '');
                }
            }
            break;
        case 'bulk_add_episodes':
            if ($u = current_user()) {
                if (!empty($_POST['series_id']) && isset($_POST['season']) && isset($_POST['count'])) {
                    $seriesId = (int)$_POST['series_id'];
                    $season = (int)$_POST['season'];
                    $count = max(0, (int)$_POST['count']);
                    $db->bulkAddEpisodes($seriesId, $season, $count);
                }
            }
            break;
        case 'import_imdb':
            if ($u = current_user()) {
                if (!empty($_POST['series_id']) && !empty($_POST['imdb_id'])) {
                    $message = import_from_imdb((int)$_POST['series_id'], trim($_POST['imdb_id']));
                }
            }
            break;
       case 'mark_watched':
           if ($u = current_user()) {
               if (!empty($_POST['episode_id'])) {
                    $db->markWatched($u['id'], (int)$_POST['episode_id'], $_POST['comment'] ?? null);
               }
           }
           break;
        case 'mark_unwatched':
            if ($u = current_user()) {
                if (!empty($_POST['episode_id'])) {
                    $db->markUnwatched($u['id'], (int)$_POST['episode_id']);
                }
            }
            break;
        case 'toggle_favorite':
            if ($u = current_user()) {
                if (!empty($_POST['episode_id'])) {
                    $db->toggleFavorite($u['id'], (int)$_POST['episode_id']);
                }
            }
            break;
        case 'update_episode':
            if ($u = current_user()) {
                if (!empty($_POST['episode_id'])) {
                    $db->updateEpisodeTitle((int)$_POST['episode_id'], $_POST['title'] ?? '');
                }
            }
            break;
    }
}

$user = current_user();
$require_login = $config['require_login'] ?? false;
if ($require_login && !$user) {
    header('Location: index.php');
    exit;
}
$edit_mode = $_SESSION['edit_mode'] ?? false;

$series_id = $_GET['id'] ?? $_POST['series_id'] ?? null;
if (!$series_id) {
    header('Location: index.php');
    exit;
}

$series = $db->getSeriesById((int)$series_id);
if (!$series) {
    echo 'Series not found';
    exit;
}

function episodes_for_series_user($series_id, $uid) {
    global $db;
    return $db->getEpisodesForSeriesUser((int)$series_id, (int)$uid);
}

function episodes_for_series($series_id) {
    return episodes_for_series_user($series_id, $_SESSION['user_id'] ?? 0);
}

$episodes = episodes_for_series($series['id']);
$content = template('series', [
    'series' => $series,
    'user' => $user,
    'edit_mode' => $edit_mode,
    'episodes' => $episodes,
    'message' => $message,
    'config' => $config
]);

echo template('layout', [
    'config' => $config,
    'title' => $config['site_title'] . ' - ' . $series['title'],
    'user' => $user,
    'message' => $message,
    'content' => $content
]);
