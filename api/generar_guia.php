<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

function responder(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    responder(401, ['ok' => false, 'error' => 'Debes iniciar sesión.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['ok' => false, 'error' => 'Usa POST.']);
}

$prefijos = [
    'Servientrega' => 'SV',
    'Inter Rapidísimo' => 'IR',
    'Coordinadora' => 'CO',
    'Envia' => 'EN',
];

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$pedidoId = (int) ($data['pedido_id'] ?? 0);
$transportadora = trim($data['transportadora'] ?? '');

if ($pedidoId < 1) {
    responder(422, ['ok' => false, 'error' => 'Falta el pedido.']);
}

if (!isset($prefijos[$transportadora])) {
    responder(422, ['ok' => false, 'error' => 'Selecciona una transportadora válida.']);
}


function calcularTarifa(float $peso, string $transportadora): array
{
    $extras = [
        'Servientrega' => [2500, 2],
        'Inter Rapidísimo' => [4200, 1],
        'Coordinadora' => [1800, 2],
        'Envia' => [900, 3],
    ];

    $base = max(9000, 8500 + ($peso * 1800));

    return [
        'precio' => round($base + $extras[$transportadora][0]),
        'dias' => $extras[$transportadora][1],
    ];
}


function generarNumeroGuia(PDO $pdo, string $prefijo): string
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE guia = ?");

    do {
        $guia = $prefijo . random_int(100000000, 999999999);
        $stmt->execute([$guia]);
    } while ((int) $stmt->fetchColumn() > 0);

    return $guia;
}

try {
    $stmt = $pdo->prepare("SELECT id, estado, guia, peso_kg FROM pedidos WHERE id = ?");
    $stmt->execute([$pedidoId]);
    $pedido = $stmt->fetch();

    if (!$pedido) {
        responder(404, ['ok' => false, 'error' => 'El pedido no existe.']);
    }

    if (!empty($pedido['guia'])) {
        responder(409, ['ok' => false, 'error' => 'Este pedido ya tiene una guía.']);
    }

    if ($pedido['estado'] !== 'PEDIDO_CREADO') {
        responder(409, ['ok' => false, 'error' => 'Solo se puede generar guía a pedidos en estado "Pedido creado".']);
    }

    $guia = generarNumeroGuia($pdo, $prefijos[$transportadora]);
    $tarifa = calcularTarifa((float) $pedido['peso_kg'], $transportadora);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "UPDATE pedidos
         SET transportadora = ?, guia = ?, estado = 'GUIA_GENERADA'
         WHERE id = ? AND (guia IS NULL OR guia = '')"
    );
    $stmt->execute([$transportadora, $guia, $pedidoId]);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        responder(409, ['ok' => false, 'error' => 'Este pedido ya tiene una guía.']);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO movimientos_envio (pedido_id, estado, descripcion)
         VALUES (?, 'GUIA_GENERADA', ?)"
    );
    $stmt->execute([$pedidoId, "Guía $guia generada con $transportadora"]);

    // Se guarda el precio de la tarifa elegida junto con la guía.
    $stmt = $pdo->prepare(
        "INSERT INTO cotizaciones (pedido_id, transportadora, precio, dias_estimados)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$pedidoId, $transportadora, $tarifa['precio'], $tarifa['dias']]);

    $pdo->commit();

    responder(200, [
        'ok' => true,
        'guia' => $guia,
        'transportadora' => $transportadora,
        'estado' => 'GUIA_GENERADA',
        'precio' => $tarifa['precio'],
        'dias' => $tarifa['dias'],
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    responder(500, ['ok' => false, 'error' => 'No se pudo generar la guía. Inténtalo de nuevo.']);
}
