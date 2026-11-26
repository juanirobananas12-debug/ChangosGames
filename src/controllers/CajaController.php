<?php
declare(strict_types=1);

/** Movimientos de caja, registros y reportes del encargado (RF-03). */
final class CajaController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function handle(string $accion): void
    {
        require_role(['gerente', 'administrador']);
        $modelo = new Caja($this->pdo);

        switch ($accion) {
            case 'movimientos':
                json_ok([
                    'movimientos' => $modelo->movimientos((string) ($_GET['tipo'] ?? '')),
                    'resumen' => $modelo->resumen(),
                ]);
                break;

            case 'resumen':
                json_ok(['resumen' => $modelo->resumen()]);
                break;

            case 'registros':
                json_ok(['registros' => $modelo->registros((string) ($_GET['tipo'] ?? ''))]);
                break;

            case 'reporte':
                json_ok(['reporte' => $modelo->reporte()]);
                break;

            default:
                json_error('Acción no válida.', 400);
        }
    }
}
