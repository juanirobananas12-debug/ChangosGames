<?php
declare(strict_types=1);

/** Alta de clientes (RF-09) y creación/asignación de cuentas por el administrador (RF-05). */
final class CuentaController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function handle(string $accion): void
    {
        $modelo = new Usuario($this->pdo);
        $datos = body();

        switch ($accion) {
            case 'registrar-cliente':
                requeridos($datos, ['nombre', 'correo', 'usuario', 'contraseña']);
                $id = $modelo->crear(
                    texto($datos, 'nombre', 100),
                    correo_valido($datos),
                    texto($datos, 'usuario', 50),
                    (string) $datos['contraseña'],
                    $this->idRol('cliente')
                );
                json_ok(['id_usuario' => $id, 'mensaje' => 'Cuenta de cliente creada. Ya puedes iniciar sesión.'], 201);
                break;

            case 'listar':
                require_role(['gerente', 'administrador']);
                json_ok(['cuentas' => $modelo->listar(), 'roles' => $modelo->roles()]);
                break;

            case 'crear-empleado':
                $actor = require_role(['administrador']);
                requeridos($datos, ['nombre', 'correo', 'usuario', 'contraseña', 'rol']);
                $rol = opcion($datos, 'rol', ['empleado', 'gerente', 'administrador']);
                $id = $modelo->crear(
                    texto($datos, 'nombre', 100),
                    correo_valido($datos),
                    texto($datos, 'usuario', 50),
                    (string) $datos['contraseña'],
                    $this->idRol($rol)
                );
                $this->auditoria($actor['id'], 'cuenta', "Se creó la cuenta {$datos['usuario']} con rol {$rol}.");
                json_ok(['id_usuario' => $id, 'mensaje' => 'Cuenta creada correctamente.'], 201);
                break;

            case 'cambiar-rol':
                $actor = require_role(['administrador']);
                requeridos($datos, ['id_usuario', 'rol']);
                $rol = opcion($datos, 'rol', ['cliente', 'empleado', 'gerente', 'administrador']);
                $cambio = $modelo->cambiarRol(entero($datos, 'id_usuario'), $this->idRol($rol));
                if (!$cambio) {
                    json_error('No se encontró la cuenta o el rol ya era el mismo.', 404);
                }
                $this->auditoria($actor['id'], 'cuenta', "Rol cambiado a {$rol} para el usuario #{$datos['id_usuario']}.");
                json_ok(['mensaje' => 'Rol actualizado.']);
                break;

            default:
                json_error('Acción no válida.', 400);
        }
    }

    private function idRol(string $nombre): int
    {
        $stmt = $this->pdo->prepare('SELECT id_rol FROM roles WHERE nombre = :nombre');
        $stmt->execute([':nombre' => $nombre]);
        $id = $stmt->fetchColumn();
        if ($id === false) {
            json_error("El rol {$nombre} no existe.", 422);
        }
        return (int) $id;
    }

    private function auditoria(int $idUsuario, string $tipo, string $descripcion): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO registros (tipo, descripcion, id_usuario) VALUES (:tipo, :desc, :usr)'
        );
        $stmt->execute([':tipo' => $tipo, ':desc' => $descripcion, ':usr' => $idUsuario]);
    }
}
