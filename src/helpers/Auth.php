<?php
declare(strict_types=1);

/** Sesión nativa de PHP y control de permisos por rol. */
function session_start_once(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function current_user(): ?array
{
    session_start_once();
    return $_SESSION['usuario'] ?? null;
}

function login_user(array $usuario): void
{
    session_start_once();
    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id' => (int) $usuario['id_usuario'],
        'nombre' => $usuario['nombre'],
        'correo' => $usuario['correo'],
        'usuario' => $usuario['usuario'],
        'rol' => $usuario['rol'],
    ];
}

function logout_user(): void
{
    session_start_once();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_login(): array
{
    $usuario = current_user();
    if ($usuario === null) {
        json_error('Debes iniciar sesión para continuar.', 401);
    }
    return $usuario;
}

function require_role(array $roles): array
{
    $usuario = require_login();
    if (!in_array($usuario['rol'], $roles, true)) {
        json_error('No tienes permisos para esta operación.', 403);
    }
    return $usuario;
}
