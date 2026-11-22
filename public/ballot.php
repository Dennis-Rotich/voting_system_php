<?php
require __DIR__ . '/../includes/bootstrap.php';
require_login();

$pageTitle = 'Ballot';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3">Welcome, <?= e(current_user()['name']) ?></h1>
<p>The ballot will appear here.</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>