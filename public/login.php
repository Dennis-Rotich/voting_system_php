<?php
require __DIR__ . '/../includes/bootstrap.php';

if (current_user()) redirect(home_for(current_user()));

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT id, name, password_hash, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        login_user($user);
        redirect(home_for($user));
    }

    // Same message for unknown email and wrong password
    $error = 'Invalid email or password.';
}

$pageTitle = 'Log in';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Log in</h1>

<?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>

<form method="post" class="card card-body">
  <?= csrf_field() ?>
  <div class="mb-3"><label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required></div>
  <div class="mb-3"><label class="form-label">Password</label>
    <input type="password" name="password" class="form-control" required></div>
  <button class="btn btn-primary">Log in</button>
  <a href="<?= e(url('public/register.php')) ?>" class="mt-3 d-block">Need an account? Register</a>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>