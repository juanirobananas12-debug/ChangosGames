<?php
declare(strict_types=1);

/**
 * Lector mínimo de .env (sin librerías externas).
 * Prioridad: variable del entorno > archivo .env > valor por defecto.
 */
if (!function_exists('env')) {
    function env(string $clave, string $porDefecto = ''): string
    {
        $valor = getenv($clave);
        if ($valor === false) {
            $valor = $_ENV[$clave] ?? '';
        }

        if ($valor === '') {
            $archivo = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
            if (is_file($archivo)) {
                $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
                foreach ($lineas as $linea) {
                    $linea = trim($linea);
                    if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
                        continue;
                    }
                    [$claveArchivo, $valorArchivo] = explode('=', $linea, 2);
                    if (trim($claveArchivo) === $clave) {
                        $valor = trim(trim($valorArchivo), "\"'");
                        break;
                    }
                }
            }
        }

        return $valor !== '' ? $valor : $porDefecto;
    }
}
