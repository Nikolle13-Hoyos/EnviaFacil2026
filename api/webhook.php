<?php
/*
 * EJEMPLO CONCEPTUAL DE WEBHOOK.
 *
 * Una transportadora real podría enviar un POST a este endpoint
 * cuando cambie el estado de una guía.
 *
 * En producción:
 * 1. Validar firma/token de seguridad.
 * 2. Validar que la guía exista.
 * 3. Guardar el nuevo estado.
 * 4. Registrar el movimiento.
 * 5. Opcionalmente notificar al cliente.
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$guide = $data['guia'] ?? '';
$newStatus = $data['estado'] ?? '';

$allowed = ['GUIA_GENERADA','EN_TRANSITO','EN_REPARTO','ENTREGADO','CANCELADO'];

if (!$guide || !in_array($newStatus, $allowed, true)) {
    http_response_code(422);
    echo json_encode(['error' => 'Payload inválido']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM pedidos WHERE guia=?");
$stmt->execute([$guide]);
$pedido = $stmt->fetch();

if (!$pedido) {
    http_response_code(404);
    echo json_encode(['error' => 'Guía no encontrada']);
    exit;
}

$stmt = $pdo->prepare("UPDATE pedidos SET estado=? WHERE id=?");
$stmt->execute([$newStatus, $pedido['id']]);

$stmt = $pdo->prepare("INSERT INTO movimientos_envio (pedido_id, estado, descripcion) VALUES (?, ?, ?)");
$stmt->execute([$pedido['id'], $newStatus, 'Actualización recibida por webhook']);

echo json_encode(['ok' => true]);
