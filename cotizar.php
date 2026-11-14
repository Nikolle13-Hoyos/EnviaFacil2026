<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/db.php';

$stmt = $pdo->query(
    "SELECT p.id, p.origen, p.destino, p.peso_kg,
            p.valor_declarado, p.guia, p.transportadora, c.nombre AS cliente
     FROM pedidos p
     INNER JOIN clientes c ON c.id = p.cliente_id
     ORDER BY p.id DESC"
);

$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCotizaciones = count($pedidos);

// Pedido seleccionado con el botón "Ver tarifas".
$pedidoSel = null;
$tarifas = [];
$idSel = (int) ($_GET['pedido'] ?? 0);

foreach ($pedidos as $p) {
    if ((int) $p['id'] === $idSel) {
        $pedidoSel = $p;
        break;
    }
}

$tarifaGuia = null;
$tarifaEstimada = false;

if ($pedidoSel) {
    // Tarifas estimadas (misma fórmula de api/quote.php).
    // Cuando se conecten las transportadoras reales, aquí van sus precios.
    $peso = (float) $pedidoSel['peso_kg'];
    $base = max(9000, 8500 + ($peso * 1800));

    $tarifas = [
        ['transportadora' => 'Servientrega', 'precio' => round($base + 2500), 'dias' => 2],
        ['transportadora' => 'Inter Rapidísimo', 'precio' => round($base + 4200), 'dias' => 1],
        ['transportadora' => 'Coordinadora', 'precio' => round($base + 1800), 'dias' => 2],
        ['transportadora' => 'Envia', 'precio' => round($base + 900), 'dias' => 3],
    ];

    usort($tarifas, fn($a, $b) => $a['precio'] <=> $b['precio']);

    // Si el pedido ya tiene guía, se muestra el precio de esa guía.
    if (!empty($pedidoSel['guia'])) {
        $fila = false;

        try {
            $stmt = $pdo->prepare(
                "SELECT precio, dias_estimados
                 FROM cotizaciones
                 WHERE pedido_id = ? AND transportadora = ?
                 ORDER BY id DESC LIMIT 1"
            );
            $stmt->execute([$pedidoSel['id'], $pedidoSel['transportadora']]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log($e->getMessage());
        }

        if ($fila) {
            $tarifaGuia = ['precio' => $fila['precio'], 'dias' => $fila['dias_estimados']];
        } else {
            // Guías creadas antes de guardar el precio: se calcula el estimado.
            foreach ($tarifas as $t) {
                if ($t['transportadora'] === $pedidoSel['transportadora']) {
                    $tarifaGuia = ['precio' => $t['precio'], 'dias' => $t['dias']];
                }
            }
            $tarifaEstimada = true;
        }
    }
}
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


        <!-- TARIFAS DEL PEDIDO SELECCIONADO -->
        <?php if ($idSel > 0 && !$pedidoSel): ?>

            <div class="alert alert-warning">El pedido #<?= $idSel ?> no existe.</div>

        <?php elseif ($pedidoSel): ?>

            <section class="panel mb-4" id="tarifas">

                <h2>Tarifas del pedido #<?= (int) $pedidoSel['id'] ?></h2>

                <p class="muted">
                    <?= htmlspecialchars($pedidoSel['cliente'], ENT_QUOTES, 'UTF-8') ?>
                    · <?= htmlspecialchars($pedidoSel['origen'], ENT_QUOTES, 'UTF-8') ?>
                    → <?= htmlspecialchars($pedidoSel['destino'], ENT_QUOTES, 'UTF-8') ?>
                    · <?= htmlspecialchars($pedidoSel['peso_kg'], ENT_QUOTES, 'UTF-8') ?> kg
                </p>

                <div id="aviso" class="alert d-none" role="alert"></div>

                <?php if (!empty($pedidoSel['guia'])): ?>
                    <div class="alert alert-info d-flex justify-content-between align-items-center">
                        <div>
                            Este pedido ya tiene la guía
                            <strong><?= htmlspecialchars($pedidoSel['guia'], ENT_QUOTES, 'UTF-8') ?></strong>
                            con <?= htmlspecialchars($pedidoSel['transportadora'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($tarifaGuia): ?>
                                por <strong>$<?= number_format((float) $tarifaGuia['precio'], 0, ',', '.') ?></strong>
                                (<?= (int) $tarifaGuia['dias'] ?> <?= (int) $tarifaGuia['dias'] === 1 ? 'día' : 'días' ?>).
                            <?php endif; ?>
                            <?php if ($tarifaEstimada): ?>
                                <span class="d-block small">Precio estimado: la guía se creó antes de guardar el precio.</span>
                            <?php endif; ?>
                        </div>
                        <a class="btn btn-outline-primary btn-sm ms-3 text-nowrap" target="_blank"
                           href="guia_pdf.php?ids[]=<?= (int) $pedidoSel['id'] ?>">Ver guía</a>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Transportadora</th>
                                <th>Precio estimado</th>
                                <th>Tiempo de entrega</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tarifas as $i => $t): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($t['transportadora'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($i === 0): ?>
                                        <span class="badge text-bg-success ms-1">Más económica</span>
                                    <?php endif; ?>
                                    <?php if (!empty($pedidoSel['guia']) && $t['transportadora'] === $pedidoSel['transportadora']): ?>
                                        <span class="badge text-bg-primary ms-1">Guía actual</span>
                                    <?php endif; ?>
                                </td>
                                <td>$<?= number_format($t['precio'], 0, ',', '.') ?></td>
                                <td><?= (int) $t['dias'] ?> <?= $t['dias'] === 1 ? 'día' : 'días' ?></td>
                                <td class="text-end">
                                    <?php if (empty($pedidoSel['guia'])): ?>
                                        <button type="button"
                                                class="btn btn-primary btn-sm btn-elegir"
                                                data-pedido="<?= (int) $pedidoSel['id'] ?>"
                                                data-transportadora="<?= htmlspecialchars($t['transportadora'], ENT_QUOTES, 'UTF-8') ?>">
                                            Elegir y generar guía
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <p class="muted mb-0 mt-3">Los precios son estimados.</p>

            </section>

        <?php endif; ?>


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

<script>
    const aviso = document.getElementById('aviso');

    document.querySelectorAll('.btn-elegir').forEach(function (boton) {
        boton.addEventListener('click', async function () {
            const botones = document.querySelectorAll('.btn-elegir');
            botones.forEach(function (b) { b.disabled = true; });

            try {
                const respuesta = await fetch('api/generar_guia.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        pedido_id: Number(boton.dataset.pedido),
                        transportadora: boton.dataset.transportadora
                    })
                });

                if (respuesta.status === 401) {
                    window.location.href = 'login.php';
                    return;
                }

                const datos = await respuesta.json();

                if (!datos.ok) {
                    throw new Error(datos.error || 'No se pudo generar la guía.');
                }

                aviso.className = 'alert alert-success';
                aviso.textContent = 'Guía generada: ' + datos.guia;
                // Se recarga la misma página para mostrar la guía y su precio.
                setTimeout(function () {
                    window.location.href = 'cotizar.php?pedido=' + boton.dataset.pedido;
                }, 1200);
            } catch (error) {
                aviso.className = 'alert alert-danger';
                aviso.textContent = error.message;
                botones.forEach(function (b) { b.disabled = false; });
            }
        });
    });
</script>

</body>
</html>
