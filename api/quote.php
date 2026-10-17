<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Usa POST']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$origen = $data['origen'] ?? '';
$destino = $data['destino'] ?? '';
$peso = (float)($data['peso'] ?? 0);

if (!$origen || !$destino || $peso <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Origen, destino y peso son obligatorios']);
    exit;
}

/*
 * SIMULACIÓN.
 * En producción esta función llamaría APIs autorizadas de cada transportadora.
 */
$base = max(9000, 8500 + ($peso * 1800));

$quotes = [
    ['transportadora' => 'Servientrega', 'precio' => round($base + 2500), 'dias_estimados' => 2],
    ['transportadora' => 'Inter Rapidísimo', 'precio' => round($base + 4200), 'dias_estimados' => 1],
    ['transportadora' => 'Coordinadora', 'precio' => round($base + 1800), 'dias_estimados' => 2],
    ['transportadora' => 'Envia', 'precio' => round($base + 900), 'dias_estimados' => 3],
];

usort($quotes, fn($a,$b) => $a['precio'] <=> $b['precio']);

echo json_encode([
    'origen' => $origen,
    'destino' => $destino,
    'peso' => $peso,
    'cotizaciones' => $quotes
]);
