<?php
require __DIR__ . '/../includes/admin_guard.php';

$voters = db()->query("
    SELECT u.id, u.name, u.email, u.created_at,
           (SELECT COUNT(*) FROM votes v WHERE v.voter_id = u.id) AS votes_cast
    FROM users u WHERE u.role = 'voter' ORDER BY u.created_at DESC
")->fetchAll();

$pageTitle = 'Voters'; $adminActive = 'voters'; $maxWidth = '1000px';
require __DIR__ . '/../includes/admin_header.php';
?>
<h1 class="h3 mb-3">Voters</h1>
<div class="table-responsive">
<table class="table table-sm bg-white">
  <thead><tr><th>Name</th><th>Email</th><th>Registered</th><th>Positions voted</th></tr></thead>
  <tbody>
  <?php foreach ($voters as $v): ?>
    <tr><td><?= e($v['name']) ?></td><td><?= e($v['email']) ?></td>
        <td><?= e($v['created_at']) ?></td><td><?= (int)$v['votes_cast'] ?></td></tr>
  <?php endforeach; ?>
  <?php if (!$voters): ?><tr><td colspan="4" class="text-center text-muted">No voters yet.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>