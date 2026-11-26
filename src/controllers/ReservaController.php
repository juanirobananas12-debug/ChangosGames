<?php
declare(strict_types=1);

/** Reservas, entregas, cancelaciones y vencimiento (RF-11, RF-12). */
final class ReservaController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function handle(string $accion): void
    {
        $productos = new Producto($this->pdo);
        $modelo = new Reserva($this->pdo, $productos);

        switch ($accion) {
            case 'crear':
                $usuario = require_role(['cliente']);
                $datos = body();
                requeridos($datos, ['id_producto', 'cantidad']);
                $id = $modelo->crear(
                    $usuario['id'],
                    entero($datos, 'id_producto'),
                    entero($datos, 'cantidad'),
                    opcion($datos, 'metodo_entrega', ['sucursal', 'domicilio'], 'sucursal')
                );
                json_ok(['id_reserva' => $id, 'mensaje' => 'Producto reservado. Vence en 7 días.'], 201);
                break;

            case 'mias':
                $usuario = require_login();
                $modelo->expirar($usuario['id']);
                json_ok(['reservas' => $modelo->listar('', $usuario['id'])]);
                break;

            case 'listar':
                $usuario = require_role(['empleado', 'gerente', 'administrador']);
                $modelo->expirar($usuario['id']);
                $estado = (string) ($_GET['estado'] ?? '');
                json_ok(['reservas' => $modelo->listar($estado)]);
                break;

            case 'cancelar':
                $usuario = require_login();
                $datos = body();
                requeridos($datos, ['id_reserva']);
                $id = entero($datos, 'id_reserva');
                $reserva = $modelo->buscar($id);
                if ($reserva === null) {
                    json_error('La reserva no existe.', 404);
                }
                if ($usuario['rol'] === 'cliente' && (int) $reserva['id_cliente'] !== $usuario['id']) {
                    json_error('No puedes cancelar una reserva ajena.', 403);
                }
                $modelo->cancelar($id, $usuario['id'], texto($datos, 'motivo', 150));
                json_ok(['mensaje' => 'Reserva cancelada y producto devuelto al inventario.']);
                break;

            case 'confirmar-entrega':
                $usuario = require_role(['empleado', 'gerente', 'administrador']);
                $datos = body();
                requeridos($datos, ['id_reserva']);
                $modelo->confirmarEntrega(entero($datos, 'id_reserva'), $usuario['id']);
                json_ok(['mensaje' => 'Entrega confirmada.']);
                break;

            case 'expirar':
                $usuario = require_role(['empleado', 'gerente', 'administrador']);
                $cantidad = $modelo->expirar($usuario['id']);
                json_ok(['expiradas' => $cantidad, 'mensaje' => "Se expiraron {$cantidad} reservas."]);
                break;

            default:
                json_error('Acción no válida.', 400);
        }
    }
}
