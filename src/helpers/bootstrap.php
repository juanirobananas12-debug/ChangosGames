<?php
declare(strict_types=1);

/** Punto de entrada común de todos los endpoints de /api. */
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Validation.php';
require_once __DIR__ . '/Csrf.php';

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Reserva.php';
require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../models/Caja.php';

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/CuentaController.php';
require_once __DIR__ . '/../controllers/ProductoController.php';
require_once __DIR__ . '/../controllers/ReservaController.php';
require_once __DIR__ . '/../controllers/VentaController.php';
require_once __DIR__ . '/../controllers/CajaController.php';

$pdo = db();
session_start_once();

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (in_array($metodo, ['POST', 'PUT', 'DELETE'], true)) {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? (string) ($_POST['csrf'] ?? '');
    csrf_verify($token);
}
