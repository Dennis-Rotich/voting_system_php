<?php
require __DIR__ . '/../includes/admin_guard.php';


$stats = db()->query("
    SELECT
      (SELECT COUNT(*) FROM users      WHERE role = 'voter') AS voters,
      (SELECT COUNT(*) FROM elections)                       AS elections,
      (SELECT COUNT(*) FROM positions)                       AS positions,
      (SELECT COUNT(*) FROM candidates)                      AS candidates,
      (SELECT COUNT(*) FROM votes)                           AS votes
")->fetch();


$active = db()->query("
    SELECT * FROM elections WHERE status = 'open'
    ORDER BY start_at DESC LIMIT 1
")->fetch();

$turnout = null;
if ($active) {
    $stmt = db()->prepare("
        SELECT COUNT(DISTINCT v.voter_id)
        FROM votes v
        JOIN positions p ON p.id = v.position_id
        WHERE p.election_id = ?
    ");
    $stmt->execute([$active['id']]);
    $turnout = (int)$stmt->fetchColumn();
}

$pageTitle   = 'Admin dashboard';
$adminActive = 'dashboard';
$maxWidth    = '1000px';
require __DIR__ . '/../includes/admin_header.php';
?>
<h1 class="h3 mb-3">Dashboard</h1>

<div class="row g-3 mb-4">
  <?php
  $cards = [
      ['Registered voters', $stats['voters']],
      ['Elections',         $stats['elections']],
      ['Positions',         $stats['positions']],
      ['Candidates',        $stats['candidates']],
      ['Votes cast',        $stats['votes']],
  ];
  foreach ($cards as [$label, $value]): ?>
    <div class="col-6 col-md">
      <div class="card text-center h-100">
        <div class="card-body">
          <div class="display-6"><?= (int)$value ?></div>
          <div class="text-muted small"><?= e($label) ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card mb-4">
  <div class="card-header">Active election</div>
  <div class="card-body">
    <?php if ($active): ?>
      <h2 class="h5"><?= e($active['title']) ?></h2>
      <p class="mb-1 text-muted">
        <?= e($active['start_at']) ?> &rarr; <?= e($active['end_at']) ?>
      </p>
      <p class="mb-0">
        Turnout: <strong><?= $turnout ?></strong> of <?= (int)$stats['voters'] ?> voters
        <?php if ($stats['voters'] > 0): ?>
          (<?= round($turnout / $stats['voters'] * 100) ?>%)
        <?php endif; ?>
      </p>
    <?php else: ?>
      <p class="mb-0 text-muted">No election is open right now.</p>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-header">Quick actions</div>
  <div class="card-body d-flex flex-wrap gap-2">
    <a class="btn btn-primary"           href="<?= e(url('admin/elections.php')) ?>">Manage elections</a>
    <a class="btn btn-outline-primary"   href="<?= e(url('admin/positions.php')) ?>">Manage positions</a>
    <a class="btn btn-outline-primary"   href="<?= e(url('admin/candidates.php')) ?>">Manage candidates</a>
    <a class="btn btn-outline-secondary" href="<?= e(url('admin/results.php')) ?>">View results</a>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>