<?php
declare(strict_types=1);

require APP_ROOT . '/admin/helpers.php';
require APP_ROOT . '/admin/auth.php';
require APP_ROOT . '/admin/users_repository.php';
require APP_ROOT . '/admin/views.php';
require APP_ROOT . '/admin/services.php';

function admin_user_input(): array
{
    return [
        'username' => \App\Shared\Validation\FieldRules::normalize('username', trim((string) ($_POST['username'] ?? ''))),
        'name' => \App\Shared\Validation\FieldRules::normalize('person_name', trim((string) ($_POST['name'] ?? ''))),
        'role' => (string) ($_POST['role'] ?? 'admin'),
        'password' => (string) ($_POST['password'] ?? ''),
        'password_confirm' => (string) ($_POST['password_confirm'] ?? ''),
    ];
}

function admin_dispatch_module_routes(string $method, string $route): bool
{
    $routes = require APP_ROOT . '/admin/module_routes.php';
    $allowed = [];

    foreach ($routes as $definition) {
        [$routeMethod, $pattern, $handler] = $definition;

        $regex = preg_replace_callback(
            '#\{([a-zA-Z_]+)\}#',
            static function (array $matches): string {
                return $matches[1] === 'id' ? '([0-9]+)' : '([A-Za-z0-9-]+)';
            },
            $pattern
        );

        if (preg_match('#^' . $regex . '$#', $route, $matches) !== 1) {
            continue;
        }

        if ($routeMethod !== $method) {
            $allowed[$routeMethod] = $routeMethod;
            continue;
        }

        $params = [];
        foreach (array_slice($matches, 1) as $value) {
            $params[] = ctype_digit((string) $value) ? (int) $value : urldecode((string) $value);
        }

        [$class, $action] = $handler;
        $instance = new $class();
        $instance->$action(...$params);

        return true;
    }

    if ($allowed !== []) {
        http_response_code(405);
        header('Allow: ' . implode(', ', $allowed));
        echo 'Metodo no permitido.';
        return true;
    }

    return false;
}

function admin_validate_user(array $data, ?int $id): array
{
    $errors = [];

    if ($data['username'] === '') {
        $errors['username'] = 'El usuario es obligatorio.';
    } elseif (admin_len($data['username']) < 3 || admin_len($data['username']) > 50) {
        $errors['username'] = 'Debe tener entre 3 y 50 caracteres.';
    } elseif (!preg_match('/^[A-Za-z0-9._-]+$/', $data['username'])) {
        $errors['username'] = 'Solo se permiten letras, numeros, punto, guion y guion bajo.';
    } elseif (user_username_exists($data['username'], $id)) {
        $errors['username'] = 'Ese nombre de usuario ya esta en uso.';
    }

    if (admin_len($data['name']) > 120) {
        $errors['name'] = 'El nombre no puede superar los 120 caracteres.';
    } elseif ($data['name'] !== '' && \App\Shared\Validation\FieldRules::letterCount($data['name']) < 2) {
        $errors['name'] = 'El nombre debe incluir al menos dos letras.';
    } elseif ($data['name'] !== '' && !\App\Shared\Validation\FieldRules::matches('person_name', $data['name'])) {
        $errors['name'] = \App\Shared\Validation\FieldRules::message('person_name');
    }

    if (!in_array($data['role'], ['admin', 'editor'], true)) {
        $errors['role'] = 'El rol seleccionado no es valido.';
    }

    if ($id === null || $data['password'] !== '') {
        if (strlen($data['password']) < 8) {
            $errors['password'] = 'La contrasena debe tener al menos 8 caracteres.';
        } elseif (strlen($data['password']) > 72) {
            $errors['password'] = 'La contrasena no puede superar los 72 caracteres.';
        } elseif ($data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = 'Las contrasenas no coinciden.';
        }
    }

    return $errors;
}

admin_session_start();

try {
    db()->query('SELECT 1');
} catch (Throwable $exception) {
    $detail = (bool) config('app.debug', false)
        ? $exception->getMessage()
        : 'Revisa la configuracion de la base de datos en app/inc/config.php.';

    admin_render_system_page(
        500,
        'Base de datos no disponible',
        'No se pudo conectar con la base de datos. Verifica que MySQL este activo y que las credenciales sean correctas.',
        $detail,
        '/',
        'Volver al sitio'
    );
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$route = substr($path, strlen('/admin'));
$route = '/' . trim($route, '/');

if ($route === '//') {
    $route = '/';
}

if ($route === '/login') {
    if ($method === 'POST') {
        admin_verify_csrf();

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $locked = admin_login_lock_seconds();

        if ($locked > 0) {
            admin_render_login([
                'username' => $username,
                'locked' => $locked,
                'error' => 'Demasiados intentos. Espera ' . $locked . ' segundos.',
            ]);
            return;
        }

        if ($username === '' || $password === '') {
            admin_render_login([
                'username' => $username,
                'error' => 'Ingresa tu usuario y contrasena.',
            ]);
            return;
        }

        if (admin_attempt_login($username, $password)) {
            admin_flash('success', 'Bienvenido de nuevo.');
            admin_redirect('/admin');
        }

        $locked = admin_login_lock_seconds();
        admin_render_login([
            'username' => $username,
            'locked' => $locked,
            'error' => $locked > 0
                ? 'Demasiados intentos. Espera ' . $locked . ' segundos.'
                : 'Usuario o contrasena incorrectos.',
        ]);
        return;
    }

    if (admin_current_user() !== null) {
        admin_redirect('/admin');
    }

    admin_render_login();
    return;
}

if ($route === '/logout') {
    if ($method !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        echo 'Metodo no permitido.';
        return;
    }

    admin_verify_csrf();
    admin_logout();
    session_id(bin2hex(random_bytes(16)));
    admin_session_start();
    admin_flash('success', 'Sesion cerrada correctamente.');
    admin_redirect('/admin/login');
}

$admin = admin_require_login();

if (admin_dispatch_module_routes($method, $route)) {
    return;
}

if ($route === '/' || $route === '/dashboard') {
    admin_view('dashboard', [
        'title' => 'Dashboard',
        'active' => 'dashboard',
        'usersCount' => users_count(),
        'adminCount' => users_admin_count(),
        'rafflesCount' => admin_raffle_service()->countAll(),
        'publishedRafflesCount' => admin_raffle_service()->countPublished(),
        'prizesCount' => admin_prize_service()->countAll(),
        'paymentMethodsCount' => admin_payment_method_service()->countAll(),
    ]);
    return;
}

if ($route === '/users' && $method === 'GET') {
    $search = admin_search_term();

    admin_view('users/index', [
        'title' => 'Usuarios',
        'active' => 'users',
        'users' => users_all($search !== '' ? $search : null),
        'search' => $search,
        'currentUserId' => (int) $admin['id'],
        'adminTotal' => users_admin_count(),
    ]);
    return;
}

if ($route === '/users/create' && $method === 'GET') {
    admin_view('users/form', [
        'title' => 'Nuevo usuario',
        'active' => 'users',
        'mode' => 'create',
        'user' => ['id' => null, 'username' => '', 'name' => '', 'role' => 'admin'],
        'errors' => [],
        'action' => '/admin/users',
        'isSelf' => false,
    ]);
    return;
}

if ($route === '/users' && $method === 'POST') {
    admin_verify_csrf();

    $data = admin_user_input();
    $errors = admin_validate_user($data, null);

    if ($errors !== []) {
        admin_view('users/form', [
            'title' => 'Nuevo usuario',
            'active' => 'users',
            'mode' => 'create',
            'user' => array_merge(['id' => null], $data),
            'errors' => $errors,
            'action' => '/admin/users',
            'isSelf' => false,
        ]);
        return;
    }

    user_create(
        $data['username'],
        $data['name'] !== '' ? $data['name'] : null,
        password_hash($data['password'], PASSWORD_DEFAULT),
        $data['role']
    );

    admin_flash('success', 'Usuario creado correctamente.');
    admin_redirect('/admin/users');
}

if (preg_match('#^/users/(\d+)/edit$#', $route, $matches) === 1 && $method === 'GET') {
    $user = user_find((int) $matches[1]);

    if ($user === null) {
        admin_abort(404, 'El usuario solicitado no existe.');
    }

    admin_view('users/form', [
        'title' => 'Editar usuario',
        'active' => 'users',
        'mode' => 'edit',
        'user' => $user,
        'errors' => [],
        'action' => '/admin/users/' . (int) $user['id'],
        'isSelf' => (int) $user['id'] === (int) $admin['id'],
    ]);
    return;
}

if (preg_match('#^/users/(\d+)$#', $route, $matches) === 1 && $method === 'POST') {
    admin_verify_csrf();

    $id = (int) $matches[1];
    $user = user_find($id);

    if ($user === null) {
        admin_abort(404, 'El usuario solicitado no existe.');
    }

    $data = admin_user_input();
    $errors = admin_validate_user($data, $id);

    if (
        !isset($errors['role'])
        && $user['role'] === 'admin'
        && $data['role'] !== 'admin'
        && users_admin_count() <= 1
    ) {
        $errors['role'] = 'No puedes quitar el rol de administrador al ultimo admin.';
    }

    if ($errors !== []) {
        admin_view('users/form', [
            'title' => 'Editar usuario',
            'active' => 'users',
            'mode' => 'edit',
            'user' => array_merge($user, $data),
            'errors' => $errors,
            'action' => '/admin/users/' . $id,
            'isSelf' => $id === (int) $admin['id'],
        ]);
        return;
    }

    $passwordHash = $data['password'] !== ''
        ? password_hash($data['password'], PASSWORD_DEFAULT)
        : null;

    user_update(
        $id,
        $data['username'],
        $data['name'] !== '' ? $data['name'] : null,
        $data['role'],
        $passwordHash
    );

    admin_flash('success', 'Usuario actualizado correctamente.');
    admin_redirect('/admin/users');
}

if (preg_match('#^/users/(\d+)/delete$#', $route, $matches) === 1 && $method === 'POST') {
    admin_verify_csrf();

    $id = (int) $matches[1];

    if ($id === (int) $admin['id']) {
        admin_flash('error', 'No puedes eliminar tu propio usuario.');
        admin_redirect('/admin/users');
    }

    $user = user_find($id);

    if ($user === null) {
        admin_flash('error', 'El usuario ya no existe.');
        admin_redirect('/admin/users');
    }

    if ($user['role'] === 'admin' && users_admin_count() <= 1) {
        admin_flash('error', 'No puedes eliminar al ultimo administrador.');
        admin_redirect('/admin/users');
    }

    user_delete($id);
    admin_flash('success', 'Usuario eliminado correctamente.');
    admin_redirect('/admin/users');
}

admin_abort(404, 'La ruta solicitada no existe en el panel.');
