<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../config/db.php';

$URL_RASTREO = "http://localhost/GestorDeEnvios/gestor-envios/api/tracking.php?guia=";

$datos = json_decode(file_get_contents('php://input'), true);
$pedido_id = (int)($datos['pedido_id'] ?? 0);
$canal = $datos['canal'] ?? '';

if (!$pedido_id || !in_array($canal, ['whatsapp', 'correo'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
    exit;
}

// La guía y la transportadora están en la tabla pedidos
$sql = "SELECT p.id AS pedido, p.destino, p.transportadora, p.guia, c.*
        FROM pedidos p
        INNER JOIN clientes c ON c.id = p.cliente_id
        WHERE p.id = ?
        LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$pedido_id]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$d) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Pedido no encontrado']);
    exit;
}

if (empty($d['guia'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Este pedido aún no tiene guía']);
    exit;
}

$telefono = $d['telefono'] ?? ($d['celular'] ?? '');
$correo = $d['correo'] ?? ($d['email'] ?? '');

$enlace = $URL_RASTREO . urlencode($d['guia']);
$asunto = null;
$enlace_wa = null;

if ($canal === 'whatsapp') {
    $destinatario = preg_replace('/\D/', '', $telefono);
    if ($destinatario === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'El cliente no tiene teléfono registrado']);
        exit;
    }
    if (strlen($destinatario) == 10) {
        $destinatario = '57' . $destinatario;
    }

    $mensaje = "Hola {$d['nombre']} 👋\n"
             . "Tu pedido #{$d['pedido']} ya fue despachado.\n\n"
             . "📦 Transportadora: {$d['transportadora']}\n"
             . "🔢 Número de guía: {$d['guia']}\n"
             . "📍 Destino: {$d['destino']}\n"
             . "🔎 Rastrea tu envío aquí: {$enlace}\n\n"
             . "¡Gracias por tu compra!";
    $enlace_wa = "https://wa.me/{$destinatario}?text=" . rawurlencode($mensaje);
} else {
    $destinatario = $correo;
    if ($destinatario === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'El cliente no tiene correo registrado']);
        exit;
    }
    $asunto = "Tu pedido #{$d['pedido']} ya fue despachado";
    $mensaje = "Hola {$d['nombre']},\n\n"
             . "Tu pedido #{$d['pedido']} ya fue despachado.\n\n"
             . "Transportadora: {$d['transportadora']}\n"
             . "Número de guía: {$d['guia']}\n"
             . "Destino: {$d['destino']}\n"
             . "Rastrea tu envío aquí: {$enlace}\n\n"
             . "Gracias por tu compra.\nEquipo EnvíaFácil";
}

// Simulación: en lugar de enviar el mensaje, se guarda en la base de datos
$ins = $pdo->prepare("INSERT INTO notificaciones
    (pedido_id, guia_id, canal, destinatario, asunto, mensaje, estado)
    VALUES (?, NULL, ?, ?, ?, ?, 'simulado')");
$ins->execute([$d['pedido'], $canal, $destinatario, $asunto, $mensaje]);

echo json_encode([
    'ok' => true,
    'canal' => $canal,
    'destinatario' => $destinatario,
    'asunto' => $asunto,
    'mensaje' => $mensaje,
    'enlace_wa' => $enlace_wa
]);