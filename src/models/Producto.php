<?php
declare(strict_types=1);

/** Catálogo de productos e inventario (RF-02, RF-07, RF-08, RF-10). */
final class Producto
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listar(string $busqueda = ''): array
    {
        $sql = 'SELECT p.id_producto, p.nombre, p.catalogo, p.categoria, p.clasificacion,
                       p.lote, p.precio, p.estado, p.activo,
                       COALESCE(i.cantidad_de_productos, 0) AS cantidad_de_productos
                FROM productos p
                LEFT JOIN inventario i ON i.id_producto = p.id_producto
                WHERE p.activo = 1';
        $parametros = [];
        if ($busqueda !== '') {
            $sql .= ' AND (p.nombre LIKE :q1 OR p.categoria LIKE :q2 OR p.catalogo LIKE :q3)';
            $parametros[':q1'] = '%' . $busqueda . '%';
            $parametros[':q2'] = '%' . $busqueda . '%';
            $parametros[':q3'] = '%' . $busqueda . '%';
        }
        $sql .= ' ORDER BY p.nombre';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }

    public function buscar(int $id): ?array
    {
        $sql = 'SELECT p.*, COALESCE(i.cantidad_de_productos, 0) AS cantidad_de_productos
                FROM productos p LEFT JOIN inventario i ON i.id_producto = p.id_producto
                WHERE p.id_producto = :id AND p.activo = 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila === false ? null : $fila;
    }

    public function ingresar(array $datos, ?int $idUsuario): int
    {
        $this->pdo->beginTransaction();
        try {
            $sql = 'INSERT INTO productos (nombre, catalogo, categoria, clasificacion, lote, precio, estado)
                    VALUES (:nombre, :catalogo, :categoria, :clasificacion, :lote, :precio, :estado)';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':catalogo' => $datos['catalogo'],
                ':categoria' => $datos['categoria'],
                ':clasificacion' => $datos['clasificacion'],
                ':lote' => $datos['lote'],
                ':precio' => $datos['precio'],
                ':estado' => $datos['estado'],
            ]);
            $id = (int) $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare('INSERT INTO inventario (id_producto, cantidad_de_productos) VALUES (:id, :cantidad)');
            $stmt->execute([':id' => $id, ':cantidad' => $datos['cantidad']]);

            $this->auditoria('ingreso_producto', $id, "Ingreso de producto {$datos['nombre']} (lote {$datos['lote']})", $idUsuario);
            $this->pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function actualizar(int $id, array $datos, ?int $idUsuario): bool
    {
        $sql = 'UPDATE productos SET nombre = :nombre, catalogo = :catalogo, categoria = :categoria,
                clasificacion = :clasificacion, lote = :lote, precio = :precio, estado = :estado
                WHERE id_producto = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':nombre' => $datos['nombre'],
            ':catalogo' => $datos['catalogo'],
            ':categoria' => $datos['categoria'],
            ':clasificacion' => $datos['clasificacion'],
            ':lote' => $datos['lote'],
            ':precio' => $datos['precio'],
            ':estado' => $datos['estado'],
        ]);
        $this->auditoria('ingreso_producto', $id, "Actualización de producto #{$id}", $idUsuario);
        return $stmt->rowCount() > 0;
    }

    /** Ajuste atómico del stock; devuelve false si no hay existencias suficientes. */
    public function ajustarStock(int $id, int $delta): bool
    {
        $sql = 'UPDATE inventario
                SET cantidad_de_productos = cantidad_de_productos + :delta1
                WHERE id_producto = :id AND cantidad_de_productos + :delta2 >= 0';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':delta1' => $delta, ':delta2' => $delta, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    private function auditoria(string $tipo, int $referencia, string $descripcion, ?int $idUsuario): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO registros (tipo, referencia_id, descripcion, id_usuario) VALUES (:tipo, :ref, :desc, :usr)'
        );
        $stmt->execute([':tipo' => $tipo, ':ref' => $referencia, ':desc' => $descripcion, ':usr' => $idUsuario]);
    }
}
