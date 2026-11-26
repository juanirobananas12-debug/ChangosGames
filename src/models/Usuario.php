<?php
declare(strict_types=1);

/** Usuarios y roles (Administrador, Empleado, Gerente/Encargado, Cliente). */
final class Usuario
{
    public function __construct(private PDO $pdo)
    {
    }

    public function roles(): array
    {
        return $this->pdo->query('SELECT id_rol, nombre FROM roles ORDER BY id_rol')->fetchAll();
    }

    public function porUsuario(string $usuario): ?array
    {
        $sql = 'SELECT u.*, r.nombre AS rol
                FROM usuarios u JOIN roles r ON r.id_rol = u.id_rol
                WHERE u.usuario = :usuario AND u.activo = 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':usuario' => $usuario]);
        $fila = $stmt->fetch();
        return $fila === false ? null : $fila;
    }

    public function porId(int $id): ?array
    {
        $sql = 'SELECT u.id_usuario, u.nombre, u.correo, u.usuario, u.activo, r.nombre AS rol
                FROM usuarios u JOIN roles r ON r.id_rol = u.id_rol WHERE u.id_usuario = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila === false ? null : $fila;
    }

    public function listar(): array
    {
        return $this->pdo->query(
            'SELECT u.id_usuario, u.nombre, u.correo, u.usuario, u.activo, u.id_rol, r.nombre AS rol, u.creado_en
             FROM usuarios u JOIN roles r ON r.id_rol = u.id_rol ORDER BY u.id_usuario'
        )->fetchAll();
    }

    public function crear(string $nombre, string $correo, string $usuario, string $contraseña, int $idRol): int
    {
        if ($this->existe('usuario', $usuario) || $this->existe('correo', $correo)) {
            throw new RuntimeException('El nombre de usuario o el correo ya están registrados.');
        }

        $sql = 'INSERT INTO usuarios (id_rol, nombre, correo, usuario, password_hash)
                VALUES (:rol, :nombre, :correo, :usuario, :hash)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':rol' => $idRol,
            ':nombre' => $nombre,
            ':correo' => $correo,
            ':usuario' => $usuario,
            ':hash' => password_hash($contraseña, PASSWORD_DEFAULT),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function cambiarRol(int $id, int $idRol): bool
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET id_rol = :rol WHERE id_usuario = :id');
        $stmt->execute([':rol' => $idRol, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    private function existe(string $campo, string $valor): bool
    {
        $permitidos = ['usuario', 'correo'];
        if (!in_array($campo, $permitidos, true)) {
            return false;
        }
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE {$campo} = :valor");
        $stmt->execute([':valor' => $valor]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
