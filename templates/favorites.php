<h2>Favorites</h2>
<table class="table table-sm episode-table">
  <colgroup>
    <col style="width:5%">
    <col style="width:25%">
    <col style="width:15%">
    <col style="width:55%">
  </colgroup>
  <thead>
    <tr><th></th><th>Series</th><th>Episode</th><th>Title</th></tr>
  </thead>
  <tbody>
  <?php foreach ($favorites as $f): ?>
    <tr data-id="<?= $f['id'] ?>">
      <td>
        <form method="post" action="index.php?page=favorites">
          <input type="hidden" name="action" value="toggle_favorite">
          <input type="hidden" name="episode_id" value="<?= $f['id'] ?>">
          <button class="btn btn-link p-0 border-0" style="color:gold">&#9733;</button>
        </form>
      </td>
      <td><a href="index.php?page=series&id=<?= $f['series_id'] ?>"><?= htmlspecialchars($f['series_title']) ?></a></td>
      <td><?= htmlspecialchars($f['season']) ?>x<?= htmlspecialchars($f['episode']) ?></td>
      <td><?= htmlspecialchars($f['title']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<script src="/Sortable.min.js"></script>
<script>
var tb = document.querySelector('.episode-table tbody');
if (tb) {
  new Sortable(tb, {
    animation: 150,
    onEnd: function(){
      const order = Array.from(tb.children).map(tr => tr.dataset.id);
      fetch('index.php?page=reorder', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({order})
      });
    }
  });
}
</script>
