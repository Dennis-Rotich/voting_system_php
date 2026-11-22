<?php
require __DIR__ . '/../includes/bootstrap.php';

if (current_user()) redirect(home_for(current_user()));

$errors = [];
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $name     = trim($_POST['name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($name === '' || mb_strlen($name) > 100)          $errors[] = 'Enter your name (max 100 characters).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150)
                                                         $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 8)                           $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm)                          $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'That email is already registered.';
        }
    }

    if (!$errors) {
        try {
            $stmt = db()->prepare(
                'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, "voter")'
            );
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            flash('success', 'Account created. You can log in now.');
            redirect('public/login.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {      // duplicate email (race condition)
                $errors[] = 'That email is already registered.';
            } else {
                throw $e;
            }
        }
    }
}

$pageTitle = 'Register';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Create voter account</h1>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" class="card card-body">
  <?= csrf_field() ?>
  <div class="mb-3"><label class="form-label">Full name</label>
    <input name="name" class="form-control" value="<?= e($name) ?>" required maxlength="100"></div>
  <div class="mb-3"><label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required maxlength="150"></div>
  <div class="mb-3"><label class="form-label">Password (min 8 characters)</label>
    <input type="password" name="password" class="form-control" required minlength="8"></div>
  <div class="mb-3"><label class="form-label">Confirm password</label>
    <input type="password" name="confirm" class="form-control" required></div>
  <button class="btn btn-primary">Register</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>