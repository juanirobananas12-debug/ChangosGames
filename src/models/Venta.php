<?php
declare(strict_types=1);

/** Ventas: efectivo/tarjeta, registro de caja, cancelación y actualización de inventario. */
final class Venta
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Convertir una reserva en venta: pagada -> registrada.
     * El inventario ya se descontó al reservar, aquí solo se registra caja y auditoría.
     */
    public function crearDesdeReserva(int $idReserva, string $metodoPago, int $idEmpleado): int
    {
        $sql = 'SELECT r.*, p.precio, p.nombre AS producto
                FROM reservas r JOIN productos p ON p.id_producto = r.id_producto
                WHERE r.id_reserva = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $idReserva]);
        $reserva = $stmt->fetch();
        if ($reserva === false) {
            throw new RuntimeException('La reserva no existe.');
        }
        if ($reserva['estado'] !== 'reservada') {
            throw new RuntimeException('La reserva no está disponible para vender.');
        }
        if (strtotime((string) $reserva['vence_en']) < time()) {
            throw new RuntimeException('La reserva está vencida; debe cancelarse o expirarse.');
        }

        $total = round((float) $reserva['precio'] * (int) $reserva['cantidad'], 2);
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO ventas (numero_venta, id_reserva, id_cliente, id_empleado, total_venta, metodo_de_pago, estado)
                 VALUES (:numero, :reserva, :cliente, :empleado, :total, :metodo, "pagada")'
            );
            $stmt->execute([
                ':numero' => 'TEMP-' . $idReserva . '-' . time(),
                ':reserva' => $idReserva,
                ':cliente' => $reserva['id_cliente'],
                ':empleado' => $idEmpleado,
                ':total' => $total,
                ':metodo' => $metodoPago,
            ]);
            $idVenta = (int) $this->pdo->lastInsertId();
            $numero = 'V-' . str_pad((string) $idVenta, 6, '0', STR_PAD_LEFT);
            $stmt = $this->pdo->prepare('UPDATE ventas SET numero_venta = :numero WHERE id_venta = :id');
            $stmt->execute([':numero' => $numero, ':id' => $idVenta]);

            $stmt = $this->pdo->prepare(
                'INSERT INTO venta_detalles (id_venta, id_producto, cantidad, precio_unitario)
                 VALUES (:venta, :producto, :cantidad, :precio)'
            );
            $stmt->execute([
                ':venta' => $idVenta,
                ':producto' => $reserva['id_producto'],
                ':cantidad' => $reserva['cantidad'],
                ':precio' => $reserva['precio'],
            ]);

            $stmt = $this->pdo->prepare(
                'INSERT INTO movimientos_caja (tipo, concepto, monto, metodo_pago, id_venta)
                 VALUES ("ingreso", :concepto, :monto, :metodo, :venta)'
            );
            $stmt->execute([
                ':concepto' => 'Venta ' . $numero . ' - ' . $reserva['producto'],
                ':monto' => $total,
                ':metodo' => $metodoPago,
                ':venta' => $idVenta,
            ]);

            $stmt = $this->pdo->prepare(
                'INSERT INTO registros (tipo, referencia_id, descripcion, id_usuario)
                 VALUES ("venta", :ref, :desc, :usr)'
            );
            $stmt->execute([
                ':ref' => $idVenta,
                ':desc' => "Venta {$numero} registrada por {$metodoPago}. Total: {$total}",
                ':usr' => $idEmpleado,
            ]);

            $stmt = $this->pdo->prepare("UPDATE ventas SET estado = 'registrada' WHERE id_venta = :id");
            $stmt->execute([':id' => $idVenta]);
            $stmt = $this->pdo->prepare("UPDATE reservas SET estado = 'pagada' WHERE id_reserva = :id");
            $stmt->execute([':id' => $idReserva]);

            $this->pdo->commit();
            return $idVenta;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Cancelar venta: devolver stock y asentar egreso en caja. */
    public function cancelar(int $idVenta, int $idUsuario, string $motivo): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ventas WHERE id_venta = :id');
        $stmt->execute([':id' => $idVenta]);
        $venta = $stmt->fetch();
        if ($venta === false) {
            throw new RuntimeException('La venta no existe.');
        }
        if ($venta['estado'] === 'cancelada') {
            throw new RuntimeException('La venta ya está cancelada.');
        }

        $stmt = $this->pdo->prepare('SELECT id_producto, cantidad FROM venta_detalles WHERE id_venta = :id');
        $stmt->execute([':id' => $idVenta]);
        $detalles = $stmt->fetchAll();

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("UPDATE ventas SET estado = 'cancelada' WHERE id_venta = :id");
            $stmt->execute([':id' => $idVenta]);
            foreach ($detalles as $detalle) {
                $stmt = $this->pdo->prepare(
                    'UPDATE inventario SET cantidad_de_productos = cantidad_de_productos + :cantidad WHERE id_producto = :id'
                );
                $stmt->execute([':cantidad' => $detalle['cantidad'], ':id' => $detalle['id_producto']]);
            }
            $stmt = $this->pdo->prepare(
                'INSERT INTO movimientos_caja (tipo, concepto, monto, metodo_pago, id_venta)
                 VALUES ("egreso", :concepto, :monto, :metodo, :venta)'
            );
            $stmt->execute([
                ':concepto' => 'Cancelación de ' . $venta['numero_venta'] . '. ' . $motivo,
                ':monto' => $venta['total_venta'],
                ':metodo' => $venta['metodo_de_pago'],
                ':venta' => $idVenta,
            ]);
            $stmt = $this->pdo->prepare(
                'INSERT INTO registros (tipo, referencia_id, descripcion, id_usuario)
                 VALUES ("cancelacion", :ref, :desc, :usr)'
            );
            $stmt->execute([
                ':ref' => $idVenta,
                ':desc' => "Venta {$venta['numero_venta']} cancelada. Motivo: {$motivo}",
                ':usr' => $idUsuario,
            ]);
            if ($venta['id_reserva'] !== null) {
                $stmt = $this->pdo->prepare("UPDATE reservas SET estado = 'cancelada' WHERE id_reserva = :id");
                $stmt->execute([':id' => $venta['id_reserva']]);
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function listar(int $idCliente = 0): array
    {
        $sql = 'SELECT v.id_venta, v.numero_venta, v.fecha, v.total_venta, v.metodo_de_pago, v.estado,
                       c.nombre AS cliente, e.nombre AS empleado,
                       (SELECT GROUP_CONCAT(d.cantidad ORDER BY d.id_detalle SEPARATOR ", ")
                        FROM venta_detalles d WHERE d.id_venta = v.id_venta) AS cantidades
                FROM ventas v
                JOIN usuarios c ON c.id_usuario = v.id_cliente
                JOIN usuarios e ON e.id_usuario = v.id_empleado
                WHERE 1 = 1';
        $parametros = [];
        if ($idCliente > 0) {
            $sql .= ' AND v.id_cliente = :cliente';
            $parametros[':cliente'] = $idCliente;
        }
        $sql .= ' ORDER BY v.fecha DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }
}
