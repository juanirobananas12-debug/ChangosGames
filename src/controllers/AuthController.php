<?php
declare(strict_types=1);

/** Login, logout, token CSRF y sesión actual (RF-04). */
final class AuthController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function handle(string $accion): void
    {
        switch ($accion) {
            case 'token':
                json_ok(['csrf' => csrf_token()]);
                break;

            case 'yo':
                json_ok(['usuario' => current_user(), 'csrf' => csrf_token()]);
                break;

            case 'login':
                $datos = body();
                requeridos($datos, ['usuario', 'contraseña']);
                $usuarioModelo = new Usuario($this->pdo);
                $usuario = $usuarioModelo->porUsuario(texto($datos, 'usuario', 50));
                if ($usuario === null || !password_verify((string) $datos['contraseña'], $usuario['password_hash'])) {
                    json_error('Usuario o contraseña incorrectos.', 401);
                }
                login_user($usuario);
                json_ok(['usuario' => current_user()]);
                break;

            case 'logout':
                logout_user();
                json_ok(['ok' => true]);
                break;

            default:
                json_error('Acción no válida.', 400);
        }
    }
}
