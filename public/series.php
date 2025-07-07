<?php
$config = require __DIR__ . '/../config.php';

require_once __DIR__ . '/../src/DataAccess.php';
$db = new DataAccess();

session_start();
if (!isset($_SESSION['edit_mode'])) {
    $_SESSION['edit_mode'] = false;
}

function current_user(DataAccess $db) {
    if (!empty($_SESSION['user_id'])) {
        return $db->getUserById((int)$_SESSION['user_id']);
    }
    return null;
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
            if ($u = current_user($db)) {
                if (!empty($_POST['series_id']) && !empty($_POST['title'])) {
                    $db->updateSeries((int)$_POST['series_id'], $_POST['title'], $_POST['description'] ?? null);
                }
            }
            break;
        case 'add_episode':
            if ($u = current_user($db)) {
                if (!empty($_POST['series_id'])) {
                    $db->insertEpisode((int)$_POST['series_id'], $_POST['season'] !== '' ? (int)$_POST['season'] : null, $_POST['episode'] !== '' ? (int)$_POST['episode'] : null, $_POST['title'] ?? '');
                }
            }
            break;
        case 'bulk_add_episodes':
            if ($u = current_user($db)) {
                if (!empty($_POST['series_id']) && isset($_POST['season']) && isset($_POST['count'])) {
                    $seriesId = (int)$_POST['series_id'];
                    $season = (int)$_POST['season'];
                    $count = max(0, (int)$_POST['count']);
                    $db->bulkAddEpisodes($seriesId, $season, $count);
                }
            }
            break;
       case 'mark_watched':
           if ($u = current_user($db)) {
               if (!empty($_POST['episode_id'])) {
                    $db->markWatched($u['id'], (int)$_POST['episode_id'], $_POST['comment'] ?? null);
               }
           }
           break;
        case 'mark_unwatched':
            if ($u = current_user($db)) {
                if (!empty($_POST['episode_id'])) {
                    $db->markUnwatched($u['id'], (int)$_POST['episode_id']);
                }
            }
            break;
        case 'toggle_favorite':
            if ($u = current_user($db)) {
                if (!empty($_POST['episode_id'])) {
                    $db->toggleFavorite($u['id'], (int)$_POST['episode_id']);
                }
            }
            break;
        case 'update_episode':
            if ($u = current_user($db)) {
                if (!empty($_POST['episode_id'])) {
                    $db->updateEpisodeTitle((int)$_POST['episode_id'], $_POST['title'] ?? '');
                }
            }
            break;
    }
}

$user = current_user($db);
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
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<title><?= htmlspecialchars($config['site_title']) ?> - <?= htmlspecialchars($series['title']) ?></title>
</head>
<body class="container py-4">
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-3">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php"><?= htmlspecialchars($config['site_title']) ?></a>
    <div class="d-flex ms-auto">
      <?php if ($user): ?>
      <a class="btn btn-outline-secondary me-2" href="favorites.php">Favorites</a>
      <a class="btn btn-outline-secondary me-2" href="config_page.php">Config</a>
      <form method="post" class="d-flex">
        <input type="hidden" name="action" value="logout">
        <button class="btn btn-secondary">Logout</button>
      </form>
      <?php else: ?>
      <form method="post" class="d-flex">
        <input type="hidden" name="action" value="login">
        <input name="username" placeholder="Username" class="form-control me-1">
        <input name="password" type="password" placeholder="Password" class="form-control me-1">
        <button class="btn btn-primary">Login</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</nav>
<?php if ($message): ?>
<div class="alert alert-warning"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<div class="card mb-3">
  <div class="card-header"><h2><?= htmlspecialchars($series['title']) ?></h2></div>
  <div class="card-body">
    <p><?= nl2br(htmlspecialchars($series['description'])) ?></p>
    <?php if ($user): ?>
    <form method="post" class="mb-3">
        <input type="hidden" name="action" value="toggle_edit_mode">
        <input type="hidden" name="series_id" value="<?= $series['id'] ?>">
        <button class="btn btn-outline-secondary">
            <?= $edit_mode ? 'Bearbeiten beenden' : 'Bearbeiten' ?>
        </button>
    </form>
    <?php if ($edit_mode): ?>
    <h4>Edit Series</h4>
    <form method="post" class="mb-3">
        <input type="hidden" name="action" value="update_series">
        <input type="hidden" name="series_id" value="<?= $series['id'] ?>">
        <input name="title" value="<?= htmlspecialchars($series['title']) ?>" class="form-control mb-1">
        <textarea name="description" class="form-control mb-1"><?= htmlspecialchars($series['description']) ?></textarea>
        <button class="btn btn-primary">Save</button>
    </form>
    <h4>Add Episode</h4>
    <form method="post" class="mb-3">
        <input type="hidden" name="action" value="add_episode">
        <input type="hidden" name="series_id" value="<?= $series['id'] ?>">
        <input name="season" placeholder="Season" class="form-control mb-1">
        <input name="episode" placeholder="Episode" class="form-control mb-1">
        <input name="title" placeholder="Title" class="form-control mb-1">
        <button class="btn btn-success">Add Episode</button>
    </form>
    <h4>Add Season with Multiple Episodes</h4>
    <form method="post" class="mb-3">
        <input type="hidden" name="action" value="bulk_add_episodes">
        <input type="hidden" name="series_id" value="<?= $series['id'] ?>">
        <input name="season" placeholder="Season" class="form-control mb-1">
        <input name="count" placeholder="Episode Count" class="form-control mb-1">
        <button class="btn btn-success">Add Episodes</button>
    </form>
    <?php endif; ?>
    <?php endif; ?>
    <?php
        $episodes = episodes_for_series($series['id']);
        $currentSeason = null;
        foreach ($episodes as $e):
            if ($e['season'] !== $currentSeason):
                if ($currentSeason !== null):
                    echo "</tbody></table>";
                endif;
                $currentSeason = $e['season'];
                echo "<h4 class=\"mt-4\">Season " . htmlspecialchars($currentSeason) . "</h4>";
                $colgroup = "<colgroup><col style='width:5%'><col style='width:10%'>";
                $colgroup .= "<col style='width:" . ($user ? "55%" : "85%") . "'>";
                if ($user) $colgroup .= "<col style='width:30%'>";
                $colgroup .= "</colgroup>";
                echo "<table class=\"table table-sm mb-2 episode-table\">" . $colgroup;
                echo "<thead><tr><th></th><th>Ep.</th><th>Title</th>";
                if ($user) echo "<th>Actions</th>";
                echo "</tr></thead><tbody>";
            endif;
            echo "<tr data-id='" . $e['id'] . "'>";
            echo "<td>";
            echo "<form method='post' class='d-inline'>";
            echo "<input type='hidden' name='action' value='toggle_favorite'>";
            echo "<input type='hidden' name='episode_id' value='" . $e['id'] . "'>";
            $star = $e['favorite'] ? '&#9733;' : '&#9734;';
            $color = $e['favorite'] ? 'gold' : '#ccc';
            echo "<button class='btn btn-link p-0 border-0' style='color:$color'>$star</button>";
            echo "</form>";
            echo "</td>";
            echo "<td>" . htmlspecialchars($e['episode']) . "</td>";
            echo "<td>";
            if ($edit_mode) {
                echo "<form method='post' class='d-flex'>";
                echo "<input type='hidden' name='action' value='update_episode'>";
                echo "<input type='hidden' name='episode_id' value='" . $e['id'] . "'>";
                echo "<input name='title' class='form-control form-control-sm' value='" . htmlspecialchars($e['title'], ENT_QUOTES) . "'>";
                echo "<button class='btn btn-sm btn-primary ms-1'>Save</button>";
                echo "</form>";
            } else {
                echo htmlspecialchars($e['title']);
            }
            echo "</td>";
            if ($user):
                echo "<td>";
                if ($e['watched']):
                    echo "<span class=\"badge bg-success me-1\">Watched</span>";
                    echo "<form method=\"post\" class=\"d-inline\">";
                    echo "<input type=\"hidden\" name=\"action\" value=\"mark_unwatched\">";
                    echo "<input type=\"hidden\" name=\"episode_id\" value=\"" . $e['id'] . "\">";
                    echo "<button class=\"btn btn-sm btn-warning\">Unwatch</button>";
                    echo "</form>";
                else:
                    echo "<form method=\"post\" class=\"row gx-1 gy-1 align-items-center\">";
                    echo "<input type=\"hidden\" name=\"action\" value=\"mark_watched\">";
                    echo "<input type=\"hidden\" name=\"episode_id\" value=\"" . $e['id'] . "\">";
                    echo "<div class=\"col\"><input name=\"comment\" class=\"form-control form-control-sm\" placeholder=\"Comment\"></div>";
                    echo "<div class=\"col-auto\"><button class=\"btn btn-sm btn-primary\">Watch</button></div>";
                    echo "</form>";
                endif;
                if ($e['comment']):
                    echo "<div class=\"small fst-italic\">" . htmlspecialchars($e['comment']) . "</div>";
                endif;
                echo "</td>";
            endif;
            echo "</tr>";
        endforeach;
        if ($currentSeason !== null) echo "</tbody></table>";
    ?>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.querySelectorAll('.episode-table tbody').forEach(function(tb){
  new Sortable(tb, {
    animation: 150,
    onEnd: function(){
      const order = Array.from(tb.children).map(tr => tr.dataset.id);
      fetch('reorder.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({order})
      });
    }
  });
});
</script>
</body>
</html>
