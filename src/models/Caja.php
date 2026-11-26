<?php
declare(strict_types=1);

/** Movimientos de caja, ingresos, registros y reportes (RF-03 y caso 'Generar reportes'). */
final class Caja
{
    public function __construct(private PDO $pdo)
    {
    }

    public function movimientos(string $tipo = ''): array
    {
        $sql = 'SELECT m.id_movimiento, m.tipo, m.concepto, m.monto, m.metodo_pago, m.creado_en, v.numero_venta
                FROM movimientos_caja m LEFT JOIN ventas v ON v.id_venta = m.id_venta
                WHERE 1 = 1';
        $parametros = [];
        if (in_array($tipo, ['ingreso', 'egreso'], true)) {
            $sql .= ' AND m.tipo = :tipo';
            $parametros[':tipo'] = $tipo;
        }
        $sql .= ' ORDER BY m.creado_en DESC LIMIT 200';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }

    public function resumen(): array
    {
        $sql = 'SELECT
                    COALESCE(SUM(CASE WHEN tipo = "ingreso" THEN monto ELSE 0 END), 0) AS ingresos,
                    COALESCE(SUM(CASE WHEN tipo = "egreso" THEN monto ELSE 0 END), 0) AS egresos
                FROM movimientos_caja';
        $fila = $this->pdo->query($sql)->fetch();
        $ingresos = (float) ($fila['ingresos'] ?? 0);
        $egresos = (float) ($fila['egresos'] ?? 0);
        return [
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'saldo' => round($ingresos - $egresos, 2),
        ];
    }

    public function registros(string $tipo = ''): array
    {
        $sql = 'SELECT r.id_registro, r.tipo, r.descripcion, r.creado_en, u.nombre AS usuario
                FROM registros r LEFT JOIN usuarios u ON u.id_usuario = r.id_usuario
                WHERE 1 = 1';
        $parametros = [];
        if ($tipo !== '') {
            $sql .= ' AND r.tipo = :tipo';
            $parametros[':tipo'] = $tipo;
        }
        $sql .= ' ORDER BY r.creado_en DESC LIMIT 200';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }

    public function reporte(): array
    {
        $porMetodo = $this->pdo->query(
            'SELECT metodo_de_pago, COUNT(*) AS ventas, COALESCE(SUM(total_venta), 0) AS total
             FROM ventas WHERE estado <> "cancelada" GROUP BY metodo_de_pago ORDER BY metodo_de_pago'
        )->fetchAll();

        $topProductos = $this->pdo->query(
            'SELECT p.nombre, SUM(d.cantidad) AS unidades, SUM(d.cantidad * d.precio_unitario) AS total
             FROM venta_detalles d
             JOIN ventas v ON v.id_venta = d.id_venta AND v.estado <> "cancelada"
             JOIN productos p ON p.id_producto = d.id_producto
             GROUP BY p.id_producto ORDER BY unidades DESC LIMIT 10'
        )->fetchAll();

        return [
            'resumen' => $this->resumen(),
            'por_metodo' => $porMetodo,
            'productos' => $topProductos,
            'generado_en' => date('Y-m-d H:i:s'),
        ];
    }
}
