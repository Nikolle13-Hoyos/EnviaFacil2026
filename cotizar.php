
<?php
require_once __DIR__ . '/config/db.php';

$stmt = $pdo->query(
    "SELECT p.id, p.origen, p.destino, p.peso_kg,
            p.valor_declarado, c.nombre AS cliente
     FROM pedidos p
     INNER JOIN clientes c ON c.id = p.cliente_id
     ORDER BY p.id DESC"
);

$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis cotizaciones</title>
    <link rel="icon" type="image/png" href="assets/img/logofondo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="simple-page">
<div class="container py-5">
    <a href="index.php" class="text-decoration-none">← Volver</a>

    <div class="form-card mt-3">
        <h1>Mis cotizaciones</h1>
        <p class="text-secondary">
            Consulta las tarifas estimadas de tus pedidos.
        </p>

        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                <tr>
                    <th>Pedido</th>
                    <th>Cliente</th>
                    <th>Origen</th>
                    <th>Destino</th>
                    <th>Peso</th>
                    <th>Acción</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$pedidos): ?>
                    <tr>
                        <td colspan="6" class="text-center">
                            No hay pedidos registrados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pedidos as $p): ?>
                        <tr>
                            <td>#<?= (int)$p['id'] ?></td>
                            <td><?= htmlspecialchars($p['cliente']) ?></td>
                            <td><?= htmlspecialchars($p['origen']) ?></td>
                            <td><?= htmlspecialchars($p['destino']) ?></td>
                            <td><?= htmlspecialchars($p['peso_kg']) ?> kg</td>
                            <td>
                                <a class="btn btn-primary btn-sm"
                                   href="cotizar.php?pedido=<?= (int)$p['id'] ?>">
                                    Ver tarifas
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
