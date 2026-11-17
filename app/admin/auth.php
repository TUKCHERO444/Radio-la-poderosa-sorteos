<?php
declare(strict_types=1);

function admin_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_name('radiolp_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function admin_csrf_token(): string
{
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['admin_csrf'];
}

function admin_csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(admin_csrf_token()) . '">';
}

function admin_verify_csrf(): void
{
    $token = $_POST['_token'] ?? '';

    if (!is_string($token) || !hash_equals(admin_csrf_token(), $token)) {
        admin_render_system_page(
            419,
            'Sesion expirada',
            'El formulario expiro o la solicitud no es valida. Vuelve a iniciar sesion para continuar.',
            '',
            '/admin/login',
            'Volver a iniciar sesion'
        );
    }
}

function admin_current_user(): ?array
{
    static $loaded = false;
    static $user = null;

    if ($loaded) {
        return $user;
    }

    $loaded = true;
    $id = (int) ($_SESSION['admin_user_id'] ?? 0);

    if ($id > 0) {
        $found = user_find($id);
        if ($found === null) {
            unset($_SESSION['admin_user_id']);
        } else {
            $user = $found;
        }
    }

    return $user;
}

function admin_require_login(): array
{
    $user = admin_current_user();

    if ($user === null) {
        admin_flash('error', 'Inicia sesion para continuar.');
        admin_redirect('/admin/login');
    }

    return $user;
}

function admin_login_lock_seconds(): int
{
    $until = (int) ($_SESSION['admin_login_locked_until'] ?? 0);

    return max(0, $until - time());
}

function admin_login_register_failure(): void
{
    $attempts = (int) ($_SESSION['admin_login_attempts'] ?? 0) + 1;

    if ($attempts >= 5) {
        $_SESSION['admin_login_attempts'] = 0;
        $_SESSION['admin_login_locked_until'] = time() + 60;
        return;
    }

    $_SESSION['admin_login_attempts'] = $attempts;
}

function admin_attempt_login(string $username, string $password): bool
{
    if (admin_login_lock_seconds() > 0) {
        return false;
    }

    $user = user_find_by_username($username);

    if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
        admin_login_register_failure();
        return false;
    }

    if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
        user_update_password((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
    }

    session_regenerate_id(true);
    $_SESSION['admin_user_id'] = (int) $user['id'];
    $_SESSION['admin_login_at'] = time();
    unset(
        $_SESSION['admin_csrf'],
        $_SESSION['admin_login_attempts'],
        $_SESSION['admin_login_locked_until']
    );

    user_touch_login((int) $user['id']);

    return true;
}

function admin_logout(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    $_SESSION = [];

    if ((bool) ini_get('session.use_cookies')) {
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    session_destroy();
}
