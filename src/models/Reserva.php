<?php
declare(strict_types=1);

/** Reservas: flujo del diagrama de actividades y estados (RF-11, RF-12). */
final class Reserva
{
    public function __construct(private PDO $pdo, private Producto $productos)
    {
    }

    /** Crear reserva: descuenta stock, fija vencimiento a 7 días y pasa a 'reservada'. */
    public function crear(int $idCliente, int $idProducto, int $cantidad, string $entrega): int
    {
        $producto = $this->productos->buscar($idProducto);
        if ($producto === null) {
            throw new RuntimeException('El producto solicitado no existe.');
        }
        if ((int) $producto['cantidad_de_productos'] < $cantidad) {
            throw new RuntimeException('No hay existencias suficientes para reservar.');
        }

        $this->pdo->beginTransaction();
        try {
            if (!$this->productos->ajustarStock($idProducto, -$cantidad)) {
                throw new RuntimeException('No hay existencias suficientes para reservar.');
            }
            $vence = date('Y-m-d H:i:s', strtotime('+7 days'));
            $stmt = $this->pdo->prepare(
                'INSERT INTO reservas (id_cliente, id_producto, cantidad, metodo_entrega, estado, vence_en)
                 VALUES (:cliente, :producto, :cantidad, :entrega, "reservada", :vence)'
            );
            $stmt->execute([
                ':cliente' => $idCliente,
                ':producto' => $idProducto,
                ':cantidad' => $cantidad,
                ':entrega' => $entrega,
                ':vence' => $vence,
            ]);
            $id = (int) $this->pdo->lastInsertId();
            $nota = $entrega === 'domicilio'
                ? 'Reserva #' . $id . ' con entrega a domicilio: pendiente de notificar al repartidor.'
                : 'Reserva #' . $id . ' con entrega en sucursal: notificación por correo (simulada).';
            $this->auditoria('reserva', $id, $nota, $idCliente);
            $this->pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function listar(string $estado = '', int $idCliente = 0): array
    {
        $sql = 'SELECT r.id_reserva, r.cantidad, r.metodo_entrega, r.estado, r.vence_en, r.creada_en,
                       p.nombre AS producto, p.precio, u.nombre AS cliente, u.usuario
                FROM reservas r
                JOIN productos p ON p.id_producto = r.id_producto
                JOIN usuarios u ON u.id_usuario = r.id_cliente
                WHERE 1 = 1';
        $parametros = [];
        if ($estado !== '') {
            $sql .= ' AND r.estado = :estado';
            $parametros[':estado'] = $estado;
        }
        if ($idCliente > 0) {
            $sql .= ' AND r.id_cliente = :cliente';
            $parametros[':cliente'] = $idCliente;
        }
        $sql .= ' ORDER BY r.creada_en DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }

    public function buscar(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM reservas WHERE id_reserva = :id');
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila === false ? null : $fila;
    }

    /** Cancelar reserva y devolver el producto al inventario. */
    public function cancelar(int $id, ?int $idUsuario, string $motivo = ''): void
    {
        $reserva = $this->buscar($id);
        if ($reserva === null) {
            throw new RuntimeException('La reserva no existe.');
        }
        if ($reserva['estado'] !== 'reservada') {
            throw new RuntimeException('Solo se pueden cancelar reservas activas.');
        }

        $this->pdo->beginTransaction();
        try {
            $this->productos->ajustarStock((int) $reserva['id_producto'], (int) $reserva['cantidad']);
            $stmt = $this->pdo->prepare("UPDATE reservas SET estado = 'cancelada' WHERE id_reserva = :id");
            $stmt->execute([':id' => $id]);
            $this->auditoria('reserva', $id, 'Reserva cancelada. ' . $motivo . ' Notificación por correo (simulada).', $idUsuario);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Vencimiento automático a los 7 días (regla del diagrama de actividades). */
    public function expirar(?int $idUsuario): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT id_reserva, id_producto, cantidad FROM reservas
             WHERE estado = 'reservada' AND vence_en < NOW()"
        );
        $stmt->execute();
        $vencidas = $stmt->fetchAll();
        $cantidad = 0;

        $this->pdo->beginTransaction();
        try {
            foreach ($vencidas as $reserva) {
                $this->productos->ajustarStock((int) $reserva['id_producto'], (int) $reserva['cantidad']);
                $update = $this->pdo->prepare("UPDATE reservas SET estado = 'vencida' WHERE id_reserva = :id");
                $update->execute([':id' => $reserva['id_reserva']]);
                $this->auditoria('reserva', (int) $reserva['id_reserva'], 'Reserva vencida después de 7 días; stock restaurado.', $idUsuario);
                $cantidad++;
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        return $cantidad;
    }

    /** Confirmación de entrega a domicilio por el encargado. */
    public function confirmarEntrega(int $id, int $idUsuario): void
    {
        $reserva = $this->buscar($id);
        if ($reserva === null) {
            throw new RuntimeException('La reserva no existe.');
        }
        if ($reserva['metodo_entrega'] !== 'domicilio') {
            throw new RuntimeException('Esta reserva no requiere entrega a domicilio.');
        }
        if (!in_array($reserva['estado'], ['pagada', 'entregada'], true)) {
            throw new RuntimeException('La reserva debe estar pagada para confirmar la entrega.');
        }
        $stmt = $this->pdo->prepare("UPDATE reservas SET estado = 'entregada' WHERE id_reserva = :id");
        $stmt->execute([':id' => $id]);
        $this->auditoria('entrega', $id, 'Entrega confirmada por el encargado; se notifica al cliente.', $idUsuario);
    }

    private function auditoria(string $tipo, int $referencia, string $descripcion, ?int $idUsuario): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO registros (tipo, referencia_id, descripcion, id_usuario) VALUES (:tipo, :ref, :desc, :usr)'
        );
        $stmt->execute([':tipo' => $tipo, ':ref' => $referencia, ':desc' => $descripcion, ':usr' => $idUsuario]);
    }
}
