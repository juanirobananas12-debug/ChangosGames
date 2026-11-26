<?php
declare(strict_types=1);

/**
 * Crea los usuarios de demostración con contraseñas cifradas.
 * Uso: php database/seed.php  (después de aplicar schema.sql y seeds.sql)
 * Contraseña de prueba para todos: Changos123!
 */

require_once __DIR__ . '/../src/config/env.php';
require_once __DIR__ . '/../src/config/database.php';

$pdo = db();

$usuarios = [
    ['administrador', 'Administrador Changos', 'admin@changosgames.local', 4],
    ['gerente', 'Encargado Changos', 'gerente@changosgames.local', 3],
    ['empleado', 'Empleado Changos', 'empleado@changosgames.local', 2],
    ['cliente', 'Cliente Changos', 'cliente@changosgames.local', 1],
];

$hash = password_hash('Changos123!', PASSWORD_DEFAULT);
$sql = 'INSERT INTO usuarios (id_rol, nombre, correo, usuario, password_hash)
        SELECT :rol, :nombre, :correo, :usuario, :hash
        WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario = :usuario2)';
$stmt = $pdo->prepare($sql);

foreach ($usuarios as $u) {
    $stmt->execute([
        ':rol' => $u[3],
        ':nombre' => $u[1],
        ':correo' => $u[2],
        ':usuario' => $u[0],
        ':hash' => $hash,
        ':usuario2' => $u[0],
    ]);
    echo "Usuario listo: {$u[0]}\n";
}

echo "Contraseña de prueba: Changos123!\n";
