<?php
declare(strict_types=1);

/** Protección CSRF nativa para las peticiones que cambian estado. */
function csrf_token(): string
{
    session_start_once();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf'];
}

function csrf_verify(string $token): void
{
    if (!hash_equals(csrf_token(), $token)) {
        json_error('Token CSRF inválido o expirado. Recarga la página.', 403);
    }
}
