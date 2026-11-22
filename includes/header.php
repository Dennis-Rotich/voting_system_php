<?php $user = current_user(); $flash = take_flash(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'Voting System') ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="<?= e(url('public/index.php')) ?>">Voting System</a>
    <div class="d-flex align-items-center gap-3">
      <?php if ($user): ?>
        <span class="text-white-50"><?= e($user['name']) ?> (<?= e($user['role']) ?>)</span>
        <form method="post" action="<?= e(url('public/logout.php')) ?>" class="m-0">
          <?= csrf_field() ?>
          <button class="btn btn-outline-light btn-sm">Log out</button>
        </form>
      <?php else: ?>
        <a class="btn btn-outline-light btn-sm" href="<?= e(url('public/login.php')) ?>">Log in</a>
        <a class="btn btn-light btn-sm" href="<?= e(url('public/register.php')) ?>">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<main class="container" style="max-width:720px">
<?php if ($flash): ?>
  <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>