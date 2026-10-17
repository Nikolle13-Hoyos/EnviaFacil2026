<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("
        SELECT p.id, c.nombre AS cliente, p.origen, p.destino, p.peso_kg,
               p.transportadora, p.guia, p.estado, p.created_at
        FROM pedidos p
        JOIN clientes c ON c.id = p.cliente_id
        ORDER BY p.created_at DESC
    ");
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    if (empty($data['nombre']) || empty($data['ciudad']) || empty($data['origen']) || empty($data['peso'])) {
        http_response_code(422);
        echo json_encode(['error' => 'Faltan datos obligatorios']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO clientes (nombre, telefono, direccion, ciudad) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $data['nombre'],
        $data['telefono'] ?? null,
        $data['direccion'] ?? null,
        $data['ciudad']
    ]);

    $clienteId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO pedidos (cliente_id, origen, destino, peso_kg, valor_declarado) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $clienteId,
        $data['origen'],
        $data['ciudad'],
        $data['peso'],
        $data['valor_declarado'] ?? 0
    ]);

    echo json_encode(['ok' => true, 'pedido_id' => $pdo->lastInsertId()]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
