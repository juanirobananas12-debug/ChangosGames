<?php
declare(strict_types=1);

/** Realizar venta con tarjeta/efectivo, consultar y cancelar ventas (RF-01, RF-06, RF-07). */
final class VentaController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function handle(string $accion): void
    {
        $modelo = new Venta($this->pdo);

        switch ($accion) {
            case 'listar':
                $usuario = require_login();
                $idCliente = $usuario['rol'] === 'cliente' ? $usuario['id'] : 0;
                json_ok(['ventas' => $modelo->listar($idCliente)]);
                break;

            case 'crear':
                $usuario = require_role(['empleado', 'gerente', 'administrador']);
                $datos = body();
                requeridos($datos, ['id_reserva', 'metodo_pago']);
                $metodo = opcion($datos, 'metodo_pago', ['efectivo', 'tarjeta']);
                $id = $modelo->crearDesdeReserva(entero($datos, 'id_reserva'), $metodo, $usuario['id']);
                json_ok(['id_venta' => $id, 'mensaje' => 'Venta realizada y registrada en caja.'], 201);
                break;

            case 'cancelar':
                $usuario = require_role(['empleado', 'gerente', 'administrador']);
                $datos = body();
                requeridos($datos, ['id_venta']);
                $modelo->cancelar(entero($datos, 'id_venta'), $usuario['id'], texto($datos, 'motivo', 150));
                json_ok(['mensaje' => 'Venta cancelada; el inventario y la caja se actualizaron.']);
                break;

            default:
                json_error('Acción no válida.', 400);
        }
    }
}
