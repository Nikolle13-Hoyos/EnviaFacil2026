<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/db.php';

$stmt = $pdo->query(
    "SELECT p.id, p.origen, p.destino, p.peso_kg,
            p.valor_declarado, c.nombre AS cliente
     FROM pedidos p
     INNER JOIN clientes c ON c.id = p.cliente_id
     ORDER BY p.id DESC"
);

$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCotizaciones = count($pedidos);
?>

<!doctype html>
<html lang="es">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Cotizaciones | EnviaFácil</title>

    <link rel="icon" type="image/png" href="assets/img/logofondo.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="assets/style.css">

</head>

<body>

<div class="app">

    <!-- BARRA LATERAL -->
    <aside class="sidebar">

        <div class="brand">
            <span>Envia</span>Fácil
        </div>

        <div class="small opacity-75 mb-3">
            Crecemos juntos, entrega a entrega.
        </div>

        <a href="index.php">Inicio</a>
        <a href="pedido.php">Pedidos</a>
        <a class="active" href="cotizar.php">Cotizaciones</a>
        <a href="guias.php">Guías</a>
        <a href="clientes.php">Clientes</a>
        <a href="index.php">Historial de envíos</a>
        <a href="reportes.php">Reportes</a>

        <div class="mt-4 pt-3 border-top border-secondary">
            <div class="small opacity-75">Sesión iniciada como</div>
            <div class="fw-semibold mb-2"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></div>
            <a href="logout.php">Cerrar sesión</a>
        </div>
    </aside>


    <!-- CONTENIDO PRINCIPAL -->
    <main class="content">

        <!-- ENCABEZADO -->
        <div class="topbar">

            <div>

                <h1>Mis cotizaciones</h1>

                <p>
                    Consulta las tarifas estimadas de tus pedidos.
                </p>

            </div>

            <a href="index.php" class="btn btn-outline-primary">
                ← Volver al inicio
            </a>

        </div>


        <!-- TARJETA DE TOTAL -->
        <div class="row g-3 mb-4">

            <div class="col-md-4">

                <div class="metric">

                    <small>Total de cotizaciones</small>

                    <strong><?= $totalCotizaciones ?></strong>

                    <span>Pedidos disponibles para cotizar</span>

                </div>

            </div>

        </div>


        <!-- PANEL DE COTIZACIONES -->
        <section class="panel">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <div>

                    <h2>Pedidos disponibles</h2>

                    <p class="muted">
                        Selecciona un pedido para consultar sus tarifas de envío.
                    </p>

                </div>

            </div>


            <!-- TABLA -->
            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

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

                            <td colspan="6" class="text-center py-4">

                                <p class="muted mb-0">
                                    No hay pedidos registrados.
                                </p>

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($pedidos as $p): ?>

                            <tr>

                                <td>
                                    #<?= (int) $p['id'] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($p['cliente'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($p['origen'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($p['destino'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($p['peso_kg'], ENT_QUOTES, 'UTF-8') ?> kg
                                </td>

                                <td>

                                    <a
                                        class="btn btn-primary btn-sm"
                                        href="cotizar.php?pedido=<?= (int) $p['id'] ?>">

                                        Ver tarifas

                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>
</html>