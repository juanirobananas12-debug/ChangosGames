<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/helpers/bootstrap.php';

(new ProductoController($pdo))->handle((string) ($_GET['accion'] ?? ''));
