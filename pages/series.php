<?php

// expects $config, $db and helper functions from bootstrap
if (!isset($_SESSION['edit_mode'])) {
    $_SESSION['edit_mode'] = false;
}

$message = '';

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
                    $db->updateSeries((int)$_POST['series_id'], $_POST['title'], $_POST['description'] ?? null);
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
    'message' => $message
]);

echo template('layout', [
    'config' => $config,
    'title' => $config['site_title'] . ' - ' . $series['title'],
    'user' => $user,
    'message' => $message,
    'content' => $content
]);
