<?php
require __DIR__ . '/../includes/admin_guard.php';

function election_for_position(int $positionId): ?array {
    $pos = find_position($positionId);
    return $pos ? find_election((int)$pos['election_id']) : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    try {
        if ($action === 'save') {
            $name       = trim($_POST['name'] ?? '');
            $manifesto  = trim($_POST['manifesto'] ?? '');
            $positionId = (int)($_POST['position_id'] ?? 0);

            if ($name === '' || mb_strlen($name) > 100) throw new RuntimeException('Enter the candidate\'s name (max 100 characters).');
            if (mb_strlen($manifesto) > 2000)          throw new RuntimeException('Manifesto is too long (max 2000 characters).');
            if (is_locked(election_for_position($positionId))) throw new RuntimeException('Pick a position from a draft election.');

            if ($id) {
                $cand = find_candidate($id);
                if (!$cand || is_locked(election_for_position((int)$cand['position_id']))) {
                    throw new RuntimeException('This candidate belongs to a locked election.');
                }
                db()->prepare('UPDATE candidates SET name=?, manifesto=?, position_id=? WHERE id=?')
                    ->execute([$name, $manifesto ?: null, $positionId, $id]);
                flash('success', 'Candidate updated.');
            } else {
                db()->prepare('INSERT INTO candidates (position_id, name, manifesto) VALUES (?,?,?)')
                    ->execute([$positionId, $name, $manifesto ?: null]);
                flash('success', 'Candidate added.');
            }

        } elseif ($action === 'delete') {
            $cand = find_candidate($id);
            if (!$cand || is_locked(election_for_position((int)$cand['position_id']))) {
                throw new RuntimeException('This candidate belongs to a locked election.');
            }
            db()->prepare('DELETE FROM candidates WHERE id=?')->execute([$id]);
            flash('success', 'Candidate deleted.');
        }
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
    } catch (PDOException $e) {
        flash('error', db_error_message($e));
    }
    redirect('admin/candidates.php');
}

$editing = isset($_GET['edit']) ? find_candidate((int)$_GET['edit']) : null;

$draftPositions = db()->query("
    SELECT p.id, p.title, e.title AS election_title
    FROM positions p JOIN elections e ON e.id = p.election_id
    WHERE e.status = 'draft' ORDER BY e.start_at DESC, p.title
")->fetchAll();

$candidates = db()->query("
    SELECT c.*, p.title AS position_title, e.title AS election_title, e.status AS election_status
    FROM candidates c
    JOIN positions p ON p.id = c.position_id
    JOIN elections e ON e.id = p.election_id
    ORDER BY e.start_at DESC, p.title, c.name
")->fetchAll();

$pageTitle   = 'Candidates';
$adminActive = 'candidates';
$maxWidth    = '1000px';
require __DIR__ . '/../includes/admin_header.php';
?>
<h1 class="h3 mb-3">Candidates</h1>

<?php if (!$draftPositions): ?>
  <div class="alert alert-info">Add a position to a draft election first.</div>
<?php else: ?>
<div class="card mb-4">
  <div class="card-header"><?= $editing ? 'Edit candidate' : 'New candidate' ?></div>
  <form method="post" class="card-body row g-3">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
    <div class="col-md-6">
      <label class="form-label">Position</label>
      <select name="position_id" class="form-select" required>
        <?php foreach ($draftPositions as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= ($editing['position_id'] ?? 0) == $p['id'] ? 'selected' : '' ?>>
            <?= e($p['election_title']) ?> &mdash; <?= e($p['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Candidate name</label>
      <input name="name" class="form-control" maxlength="100" required value="<?= e($editing['name'] ?? '') ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Manifesto (optional)</label>
      <textarea name="manifesto" class="form-control" rows="3" maxlength="2000"><?= e($editing['manifesto'] ?? '') ?></textarea>
    </div>
    <div class="col-12">
      <button class="btn btn-primary"><?= $editing ? 'Save changes' : 'Add candidate' ?></button>
      <?php if ($editing): ?><a class="btn btn-link" href="<?= e(url('admin/candidates.php')) ?>">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-sm align-middle bg-white">
  <thead><tr><th>Name</th><th>Position</th><th>Election</th><th class="text-end">Actions</th></tr></thead>
  <tbody>
  <?php foreach ($candidates as $c): $locked = $c['election_status'] !== 'draft'; ?>
    <tr>
      <td><?= e($c['name']) ?></td>
      <td><?= e($c['position_title']) ?></td>
      <td><?= e($c['election_title']) ?> <?= status_badge($c['election_status']) ?></td>
      <td class="text-end">
        <?php if ($locked): ?>
          <span class="text-muted small">Locked</span>
        <?php else: ?>
          <a class="btn btn-outline-secondary btn-sm" href="?edit=<?= (int)$c['id'] ?>">Edit</a>
          <form method="post" class="d-inline"><?= csrf_field() ?>
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this candidate?')">Delete</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$candidates): ?><tr><td colspan="4" class="text-center text-muted">No candidates yet.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>