<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$guide = trim($_GET['guide'] ?? '');

if (!$guide) {
    http_response_code(422);
    echo json_encode(['error' => 'Debes enviar la guía']);
    exit;
}

/*
 * Primero buscamos el estado local.
 * En producción, este endpoint puede consultar la API de la transportadora
 * o recibir cambios mediante webhook.
 */
$stmt = $pdo->prepare("
    SELECT p.guia, p.estado, p.updated_at, p.transportadora, c.nombre AS cliente,
           p.origen, p.destino
    FROM pedidos p
    JOIN clientes c ON c.id=p.cliente_id
    WHERE p.guia=?
");
$stmt->execute([$guide]);
$pedido = $stmt->fetch();

if (!$pedido) {
    http_response_code(404);
    echo json_encode(['error' => 'Guía no encontrada']);
    exit;
}

echo json_encode([
    'guia' => $pedido['guia'],
    'transportadora' => $pedido['transportadora'],
    'cliente' => $pedido['cliente'],
    'origen' => $pedido['origen'],
    'destino' => $pedido['destino'],
    'estado' => $pedido['estado'],
    'actualizado' => $pedido['updated_at']
]);
