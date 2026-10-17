<?php
require_once __DIR__ . '/config/db.php';

$stmt = $pdo->query("
    SELECT p.*, c.nombre AS cliente
    FROM pedidos p
    INNER JOIN clientes c ON c.id = p.cliente_id
    ORDER BY p.created_at DESC
");
$pedidos = $stmt->fetchAll();

$total = count($pedidos);
$enProceso = count(array_filter($pedidos, fn($p) => in_array($p['estado'], ['PEDIDO_CREADO','GUIA_GENERADA','EN_TRANSITO','EN_REPARTO'])));
$entregados = count(array_filter($pedidos, fn($p) => $p['estado'] === 'ENTREGADO'));
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EnviaFácil</title>
    <link rel="icon" type="image/png" href="assets/img/logofondo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand"><span>Envia</span>Fácil</div>
        <div class="small opacity-75 mb-3">Crecemos juntos, entrega a entrega.</div>
        <a class="active" href="index.php">Inicio</a>
        <a href="pedido.php">Pedidos</a>
        <a href="cotizar.php">Cotizaciones</a>
        <a href="pedido.php">Guías</a>
        <a href="clientes.php">Clientes</a>
        <a href="index.php">Historial de envíos</a>
        <a href="index.php">Reportes</a>
    </aside>

    <main class="content">
        <div class="topbar">
            <div>
                <h1>Hola!</h1>
                <p>Este es el resumen de los envíos de tu tienda.</p>
            </div>
            <a class="btn btn-primary" href="pedido.php">Nuevo pedido</a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="metric"><small>Total de envíos</small><strong><?= $total ?></strong><span>Pedidos registrados</span></div>
            </div>
            <div class="col-md-4">
                <div class="metric"><small>En proceso</small><strong><?= $enProceso ?></strong><span>Requieren seguimiento</span></div>
            </div>
            <div class="col-md-4">
                <div class="metric"><small>Entregados</small><strong><?= $entregados ?></strong><span>Completados</span></div>
            </div>
        </div>

        <section class="panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2>Últimos envíos</h2>
                    <p class="muted">Consulta rápidamente el estado de tus pedidos.</p>
                </div>
                <a href="cotizar.php" class="btn btn-outline-primary">Cotizar envío</a>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Pedido</th><th>Cliente</th><th>Destino</th><th>Transportadora</th><th>Guía</th><th>Estado</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pedidos as $p): ?>
                    <tr>
                        <td>#<?= htmlspecialchars($p['id']) ?></td>
                        <td><?= htmlspecialchars($p['cliente']) ?></td>
                        <td><?= htmlspecialchars($p['destino']) ?></td>
                        <td><?= htmlspecialchars($p['transportadora'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['guia'] ?? '-') ?></td>
                        <td><span class="status status-<?= strtolower($p['estado']) ?>"><?= htmlspecialchars(str_replace('_',' ',$p['estado'])) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
</body>
</html>
