<?php
require_once __DIR__ . '/bootstrap.php';
require_role('admin');

$pageTitle = $pageTitle ?? 'Admin';
$adminActive = $adminActive ?? '';

require __DIR__ . '/header.php';

$adminLinks = [
    'dashboard'  => ['Dashboard',  'admin/index.php'],
    'elections'  => ['Elections',  'admin/elections.php'],
    'positions'  => ['Positions',  'admin/positions.php'],
    'candidates' => ['Candidates', 'admin/candidates.php'],
    'voters'     => ['Voters',     'admin/voters.php'],
    'results'    => ['Results',    'admin/results.php'],
];
?>
<ul class="nav nav-pills mb-4 flex-wrap">
  <?php foreach ($adminLinks as $key => [$label, $path]): ?>
    <li class="nav-item">
      <a class="nav-link <?= $adminActive === $key ? 'active' : '' ?>"
         href="<?= e(url($path)) ?>"><?= e($label) ?></a>
    </li>
  <?php endforeach; ?>
</ul>