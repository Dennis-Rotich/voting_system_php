<?php
require __DIR__ . '/../includes/admin_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    try {
        if ($action === 'save') {
            $title = trim($_POST['title'] ?? '');
            $start = parse_dt($_POST['start_at'] ?? '');
            $end   = parse_dt($_POST['end_at'] ?? '');

            if ($title === '' || mb_strlen($title) > 150) throw new RuntimeException('Enter a title (max 150 characters).');
            if (!$start || !$end)                         throw new RuntimeException('Enter valid start and end times.');
            if ($end <= $start)                           throw new RuntimeException('End time must be after the start time.');

            if ($id) {
                if (!find_election($id)) throw new RuntimeException('Election not found.');
                db()->prepare('UPDATE elections SET title=?, start_at=?, end_at=? WHERE id=?')
                    ->execute([$title, $start, $end, $id]);
                flash('success', 'Election updated.');
            } else {
                db()->prepare("INSERT INTO elections (title, start_at, end_at, status) VALUES (?,?,?,'draft')")
                    ->execute([$title, $start, $end]);
                flash('success', 'Election created as a draft. Add positions and candidates next.');
            }

        } elseif ($action === 'open') {
            $el = find_election($id);
            if (!$el || $el['status'] !== 'draft') throw new RuntimeException('Only draft elections can be opened.');

            if ((int)db()->query("SELECT COUNT(*) FROM elections WHERE status='open'")->fetchColumn() > 0) {
                throw new RuntimeException('Another election is already open. Close it first.');
            }

            $q = db()->prepare('SELECT COUNT(*) FROM positions WHERE election_id = ?');
            $q->execute([$id]);
            if ((int)$q->fetchColumn() === 0) throw new RuntimeException('Add at least one position before opening.');

            $q = db()->prepare('
                SELECT COUNT(*) FROM positions p
                WHERE p.election_id = ?
                  AND (SELECT COUNT(*) FROM candidates c WHERE c.position_id = p.id) < 2');
            $q->execute([$id]);
            if ((int)$q->fetchColumn() > 0) throw new RuntimeException('Every position needs at least 2 candidates.');

            db()->prepare("UPDATE elections SET status='open' WHERE id=?")->execute([$id]);
            flash('success', 'Election is now open. The ballot is locked.');

        } elseif ($action === 'close') {
            $el = find_election($id);
            if (!$el || $el['status'] !== 'open') throw new RuntimeException('Only open elections can be closed.');
            db()->prepare("UPDATE elections SET status='closed' WHERE id=?")->execute([$id]);
            flash('success', 'Election closed. Results are now available.');

        } elseif ($action === 'delete') {
            $el = find_election($id);
            if (!$el) throw new RuntimeException('Election not found.');
            if ($el['status'] === 'open') throw new RuntimeException('Close the election before deleting it.');
            db()->prepare('DELETE FROM elections WHERE id=?')->execute([$id]);
            flash('success', 'Election deleted.');
        }
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
    } catch (PDOException $e) {
        flash('error', db_error_message($e));
    }
    redirect('admin/elections.php');
}

$editing = isset($_GET['edit']) ? find_election((int)$_GET['edit']) : null;

$elections = db()->query("
    SELECT e.*,
      (SELECT COUNT(*) FROM positions p WHERE p.election_id = e.id) AS position_count,
      (SELECT COUNT(*) FROM votes v JOIN positions p ON p.id = v.position_id
        WHERE p.election_id = e.id) AS vote_count
    FROM elections e ORDER BY e.start_at DESC
")->fetchAll();

$pageTitle   = 'Elections';
$adminActive = 'elections';
$maxWidth    = '1000px';
require __DIR__ . '/../includes/admin_header.php';
?>
<h1 class="h3 mb-3">Elections</h1>

<div class="card mb-4">
  <div class="card-header"><?= $editing ? 'Edit election' : 'New election' ?></div>
  <form method="post" class="card-body row g-3">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
    <div class="col-12">
      <label class="form-label">Title</label>
      <input name="title" class="form-control" maxlength="150" required value="<?= e($editing['title'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Starts</label>
      <input type="datetime-local" name="start_at" class="form-control" required
             value="<?= $editing ? e(dt_input($editing['start_at'])) : '' ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Ends</label>
      <input type="datetime-local" name="end_at" class="form-control" required
             value="<?= $editing ? e(dt_input($editing['end_at'])) : '' ?>">
    </div>
    <div class="col-12">
      <button class="btn btn-primary"><?= $editing ? 'Save changes' : 'Create election' ?></button>
      <?php if ($editing): ?><a class="btn btn-link" href="<?= e(url('admin/elections.php')) ?>">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="table-responsive">
<table class="table table-sm align-middle bg-white">
  <thead><tr><th>Title</th><th>Window</th><th>Status</th><th>Positions</th><th>Votes</th><th class="text-end">Actions</th></tr></thead>
  <tbody>
  <?php foreach ($elections as $el): ?>
    <tr>
      <td><?= e($el['title']) ?></td>
      <td class="small text-muted"><?= e($el['start_at']) ?><br><?= e($el['end_at']) ?></td>
      <td><?= status_badge($el['status']) ?></td>
      <td><?= (int)$el['position_count'] ?></td>
      <td><?= (int)$el['vote_count'] ?></td>
      <td class="text-end">
        <a class="btn btn-outline-secondary btn-sm" href="?edit=<?= (int)$el['id'] ?>">Edit</a>

        <?php if ($el['status'] === 'draft'): ?>
          <form method="post" class="d-inline"><?= csrf_field() ?>
            <input type="hidden" name="action" value="open"><input type="hidden" name="id" value="<?= (int)$el['id'] ?>">
            <button class="btn btn-success btn-sm" onclick="return confirm('Open this election? The ballot will be locked.')">Open</button>
          </form>
        <?php elseif ($el['status'] === 'open'): ?>
          <form method="post" class="d-inline"><?= csrf_field() ?>
            <input type="hidden" name="action" value="close"><input type="hidden" name="id" value="<?= (int)$el['id'] ?>">
            <button class="btn btn-warning btn-sm" onclick="return confirm('Close voting now? This cannot be undone.')">Close</button>
          </form>
        <?php endif; ?>

        <?php if ($el['status'] !== 'open'): ?>
          <form method="post" class="d-inline"><?= csrf_field() ?>
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$el['id'] ?>">
            <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this election and its positions and candidates?')">Delete</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$elections): ?><tr><td colspan="6" class="text-center text-muted">No elections yet.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>