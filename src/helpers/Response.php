<?php
declare(strict_types=1);

/** Respuestas JSON uniformes para la API. */
function json_ok(array $datos = [], int $estado = 200): never
{
    http_response_code($estado);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error(string $mensaje, int $estado = 400): never
{
    json_ok(['error' => $mensaje], $estado);
}
