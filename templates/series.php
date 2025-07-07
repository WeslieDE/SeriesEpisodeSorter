<div class="card mb-3">
  <div class="card-header"><h2><?= htmlspecialchars($series['title']) ?></h2></div>
  <?php if ($series['cover']): ?>
  <img src="<?= asset_url($series['cover']) ?>" class="card-img-top" loading="lazy" alt="cover">
  <?php endif; ?>
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
    <form method="post" class="mb-3" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_series">
        <input type="hidden" name="series_id" value="<?= $series['id'] ?>">
        <input name="title" value="<?= htmlspecialchars($series['title']) ?>" class="form-control mb-1">
        <textarea name="description" class="form-control mb-1"><?= htmlspecialchars($series['description']) ?></textarea>
        <input type="file" name="cover" class="form-control mb-1">
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
<script src="<?= asset_url('Sortable.min.js') ?>"></script>
<script>
document.querySelectorAll('.episode-table tbody').forEach(function(tb){
  new Sortable(tb, {
    animation: 150,
    onEnd: function(){
      const order = Array.from(tb.children).map(tr => tr.dataset.id);
      fetch('index.php?page=reorder', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({order})
      });
    }
  });
});
</script>
