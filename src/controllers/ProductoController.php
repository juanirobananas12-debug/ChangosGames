<?php
declare(strict_types=1);

/** Consulta, alta y actualización de productos (RF-02, RF-08, RF-10). */
final class ProductoController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function handle(string $accion): void
    {
        $modelo = new Producto($this->pdo);

        switch ($accion) {
            case 'listar':
                json_ok(['productos' => $modelo->listar(trim((string) ($_GET['q'] ?? '')))]);
                break;

            case 'ingresar':
                $usuario = require_role(['empleado', 'gerente', 'administrador']);
                $datos = body();
                requeridos($datos, ['nombre', 'catalogo', 'categoria', 'clasificacion', 'lote', 'precio', 'cantidad']);
                $id = $modelo->ingresar([
                    'nombre' => texto($datos, 'nombre', 120),
                    'catalogo' => texto($datos, 'catalogo', 80),
                    'categoria' => texto($datos, 'categoria', 80),
                    'clasificacion' => texto($datos, 'clasificacion', 80),
                    'lote' => texto($datos, 'lote', 50),
                    'precio' => decimal($datos, 'precio'),
                    'cantidad' => entero($datos, 'cantidad', 0),
                    'estado' => opcion($datos, 'estado', ['pristino', 'dañado'], 'pristino'),
                ], $usuario['id']);
                json_ok(['id_producto' => $id, 'mensaje' => 'Producto ingresado al inventario.'], 201);
                break;

            case 'actualizar':
                $usuario = require_role(['empleado', 'gerente', 'administrador']);
                $datos = body();
                requeridos($datos, ['id_producto', 'nombre', 'catalogo', 'categoria', 'clasificacion', 'lote', 'precio']);
                $id = entero($datos, 'id_producto');
                if ($modelo->buscar($id) === null) {
                    json_error('El producto no existe.', 404);
                }
                $modelo->actualizar($id, [
                    'nombre' => texto($datos, 'nombre', 120),
                    'catalogo' => texto($datos, 'catalogo', 80),
                    'categoria' => texto($datos, 'categoria', 80),
                    'clasificacion' => texto($datos, 'clasificacion', 80),
                    'lote' => texto($datos, 'lote', 50),
                    'precio' => decimal($datos, 'precio'),
                    'estado' => opcion($datos, 'estado', ['pristino', 'dañado'], 'pristino'),
                ], $usuario['id']);
                json_ok(['mensaje' => 'Producto actualizado.']);
                break;

            default:
                json_error('Acción no válida.', 400);
        }
    }
}
