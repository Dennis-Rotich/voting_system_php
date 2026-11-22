<?php
declare(strict_types=1);

// ---------- redirects & flash messages ----------
function redirect(string $path): void {
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ---------- CSRF ----------
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void {
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        exit('Invalid or expired form. Go back, refresh and try again.');
    }
}

// ---------- current user ----------
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function login_user(array $user): void {
    session_regenerate_id(true);   // prevents session fixation
    $_SESSION['user'] = [
        'id'   => (int)$user['id'],
        'name' => $user['name'],
        'role' => $user['role'],
    ];
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ---------- access control ----------
function require_login(): void {
    if (!current_user()) {
        flash('error', 'Please log in first.');
        redirect('public/login.php');
    }
}

function require_role(string $role): void {
    require_login();
    if (current_user()['role'] !== $role) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function home_for(array $user): string {
    return $user['role'] === 'admin' ? 'admin/index.php' : 'public/ballot.php';
}