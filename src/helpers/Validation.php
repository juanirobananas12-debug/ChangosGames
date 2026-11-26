<?php
declare(strict_types=1);

/** Validación y saneamiento básico de entradas (sin librerías). */
function body(): array
{
    $crudo = file_get_contents('php://input');
    if ($crudo === false || $crudo === '') {
        return [];
    }
    $datos = json_decode($crudo, true);
    return is_array($datos) ? $datos : [];
}

function requeridos(array $datos, array $campos): void
{
    foreach ($campos as $campo) {
        if (!isset($datos[$campo]) || trim((string) $datos[$campo]) === '') {
            json_error("El campo {$campo} es obligatorio.", 422);
        }
    }
}

function texto(array $datos, string $campo, int $maximo = 120): string
{
    $valor = trim(strip_tags((string) ($datos[$campo] ?? '')));
    return mb_substr($valor, 0, $maximo);
}

function correo_valido(array $datos, string $campo = 'correo'): string
{
    $correo = filter_var(trim((string) ($datos[$campo] ?? '')), FILTER_VALIDATE_EMAIL);
    if ($correo === false) {
        json_error('El correo electrónico no es válido.', 422);
    }
    return $correo;
}

function entero(array $datos, string $campo, int $minimo = 1): int
{
    $valor = filter_var($datos[$campo] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => $minimo]]);
    if ($valor === false) {
        json_error("El campo {$campo} debe ser un número entero mayor o igual a {$minimo}.", 422);
    }
    return (int) $valor;
}

function decimal(array $datos, string $campo): float
{
    $valor = filter_var($datos[$campo] ?? null, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0.01]]);
    if ($valor === false) {
        json_error("El campo {$campo} debe ser un número positivo.", 422);
    }
    return (float) $valor;
}

function opcion(array $datos, string $campo, array $permitidas, string $porDefecto = ''): string
{
    $valor = (string) ($datos[$campo] ?? $porDefecto);
    if (!in_array($valor, $permitidas, true)) {
        json_error("Valor no permitido para {$campo}.", 422);
    }
    return $valor;
}
