<?php
require __DIR__ . '/../includes/admin_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    try {
        if ($action === 'save') {
            $title      = trim($_POST['title'] ?? '');
            $electionId = (int)($_POST['election_id'] ?? 0);

            if ($title === '' || mb_strlen($title) > 100) throw new RuntimeException('Enter a position title (max 100 characters).');

            $election = find_election($electionId);
            if (is_locked($election)) throw new RuntimeException('Pick a draft election. Open and closed ballots are locked.');

            if ($id) {
                $pos = find_position($id);
                if (!$pos || is_locked(find_election((int)$pos['election_id']))) {
                    throw new RuntimeException('This position belongs to a locked election.');
                }
                db()->prepare('UPDATE positions SET title=?, election_id=? WHERE id=?')->execute([$title, $electionId, $id]);
                flash('success', 'Position updated.');
            } else {
                db()->prepare('INSERT INTO positions (election_id, title) VALUES (?,?)')->execute([$electionId, $title]);
                flash('success', 'Position added.');
            }

        } elseif ($action === 'delete') {
            $pos = find_position($id);
            if (!$pos || is_locked(find_election((int)$pos['election_id']))) {
                throw new RuntimeException('This position belongs to a locked election.');
            }
            db()->prepare('DELETE FROM positions WHERE id=?')->execute([$id]);
            flash('success', 'Position deleted (and its candidates).');
        }
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
    } catch (PDOException $e) {
        flash('error', db_error_message($e));
    }
    redirect('admin/positions.php');
}

$editing = isset($_GET['edit']) ? find_position((int)$_GET['edit']) : null;

$draftElections = db()->query("SELECT id, title FROM elections WHERE status='draft' ORDER BY start_at DESC")->fetchAll();

$positions = db()->query("
    SELECT p.*, e.title AS election_title, e.status AS election_status,
           (SELECT COUNT(*) FROM candidates c WHERE c.position_id = p.id) AS candidate_count
    FROM positions p JOIN elections e ON e.id = p.election_id
    ORDER BY e.start_at DESC, p.id
")->fetchAll();

$pageTitle   = 'Positions';
$adminActive = 'positions';
$maxWidth    = '1000px';
require __DIR__ . '/../includes/admin_header.php';
?>
<h1 class="h3 mb-3">Positions</h1>

<?php if (!$draftElections): ?>
  <div class="alert alert-info">Create a draft election first (positions can only be added to drafts).</div>
<?php else: ?>
<div class="card mb-4">
  <div class="card-header"><?= $editing ? 'Edit position' : 'New position' ?></div>
  <form method="post" class="card-body row g-3">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
    <div class="col-md-6">
      <label class="form-label">Election</label>
      <select name="election_id" class="form-select" required>
        <?php foreach ($draftElections as $el): ?>
          <option value="<?= (int)$el['id'] ?>" <?= ($editing['election_id'] ?? 0) == $el['id'] ? 'selected' : '' ?>>
            <?= e($el['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Position title</label>
      <input name="title" class="form-control" maxlength="100" required placeholder="e.g. President" value="<?= e($editing['title'] ?? '') ?>">
    </div>
    <div class="col-12">
      <button class="btn btn-primary"><?= $editing ? 'Save changes' : 'Add position' ?></button>
      <?php if ($editing): ?><a class="btn btn-link" href="<?= e(url('admin/positions.php')) ?>">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-sm align-middle bg-white">
  <thead><tr><th>Position</th><th>Election</th><th>Candidates</th><th class="text-end">Actions</th></tr></thead>
  <tbody>
  <?php foreach ($positions as $p): $locked = $p['election_status'] !== 'draft'; ?>
    <tr>
      <td><?= e($p['title']) ?></td>
      <td><?= e($p['election_title']) ?> <?= status_badge($p['election_status']) ?></td>
      <td><?= (int)$p['candidate_count'] ?></td>
      <td class="text-end">
        <?php if ($locked): ?>
          <span class="text-muted small">Locked</span>
        <?php else: ?>
          <a class="btn btn-outline-secondary btn-sm" href="?edit=<?= (int)$p['id'] ?>">Edit</a>
          <form method="post" class="d-inline"><?= csrf_field() ?>
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this position and its candidates?')">Delete</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$positions): ?><tr><td colspan="4" class="text-center text-muted">No positions yet.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>