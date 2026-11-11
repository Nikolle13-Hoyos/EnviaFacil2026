<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/guias.php';

$transportadoras = cargarTransportadoras();
$error = '';
$mensaje = '';

if (isset($_GET['generada'])) {
    $mensaje = 'Guía generada correctamente: ' . $_GET['generada'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pedidoId = (int) ($_POST['pedido_id'] ?? 0);
    $transportadora = trim($_POST['transportadora'] ?? '');

    try {
        $guia = crearGuiaParaPedido($pdo, $pedidoId, $transportadora);
        header('Location: guias.php?generada=' . urlencode($guia));
        exit;
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log($e->getMessage());
        $error = 'No se pudo generar la guía. Inténtalo de nuevo.';
    }
}

$sinGuia = $pdo->query(
    "SELECT p.id, p.origen, p.destino, p.peso_kg, p.valor_declarado,
            c.nombre AS cliente
     FROM pedidos p
     INNER JOIN clientes c ON c.id = p.cliente_id
     WHERE (p.guia IS NULL OR p.guia = '') AND p.estado = 'PEDIDO_CREADO'
     ORDER BY p.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$conGuia = $pdo->query(
    "SELECT p.id, p.destino, p.transportadora, p.guia, p.estado,
            c.nombre AS cliente
     FROM pedidos p
     INNER JOIN clientes c ON c.id = p.cliente_id
     WHERE p.guia IS NOT NULL AND p.guia <> ''
     ORDER BY p.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$hayModoSimulado = false;
foreach ($transportadoras as $t) {
    if ($t['modo'] !== 'api') {
        $hayModoSimulado = true;
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Guías | EnviaFácil</title>
    <link rel="icon" type="image/png" href="assets/img/logofondo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand"><span>Envia</span>Fácil</div>
        <div class="small opacity-75 mb-3">Crecemos juntos, entrega a entrega.</div>
        <a href="index.php">Inicio</a>
        <a href="pedido.php">Pedidos</a>
        <a href="cotizar.php">Cotizaciones</a>
        <a class="active" href="guias.php">Guías</a>
        <a href="clientes.php">Clientes</a>
        <a href="index.php">Historial de envíos</a>
        <a href="reportes.php">Reportes</a>

        <div class="mt-4 pt-3 border-top border-secondary">
            <div class="small opacity-75">Sesión iniciada como</div>
            <div class="fw-semibold mb-2"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></div>
            <a href="logout.php">Cerrar sesión</a>
        </div>
    </aside>

    <main class="content">
        <div class="topbar">
            <div>
                <h1>Guías de envío</h1>
                <p>Genera la guía de tus pedidos y descárgala en PDF.</p>
            </div>
            <a href="pedido.php" class="btn btn-primary">Nuevo pedido</a>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($hayModoSimulado): ?>
            <div class="alert alert-warning">
                Modo de prueba: las guías se generan localmente hasta que se conecte la API de la transportadora.
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="metric"><small>Pendientes de guía</small><strong><?= count($sinGuia) ?></strong><span>Pedidos sin guía</span></div>
            </div>
            <div class="col-md-4">
                <div class="metric"><small>Guías generadas</small><strong><?= count($conGuia) ?></strong><span>Listas para imprimir</span></div>
            </div>
        </div>

        <section class="panel mb-4">
            <h2>Pedidos sin guía</h2>
            <p class="muted">Elige la transportadora y genera la guía.</p>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Pedido</th><th>Cliente</th><th>Origen</th><th>Destino</th><th>Peso</th><th>Transportadora</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$sinGuia): ?>
                        <tr><td colspan="6" class="text-center py-4"><p class="muted mb-0">No hay pedidos pendientes de guía.</p></td></tr>
                    <?php else: ?>
                        <?php foreach ($sinGuia as $p): ?>
                        <tr>
                            <td>#<?= (int) $p['id'] ?></td>
                            <td><?= htmlspecialchars($p['cliente']) ?></td>
                            <td><?= htmlspecialchars($p['origen']) ?></td>
                            <td><?= htmlspecialchars($p['destino']) ?></td>
                            <td><?= htmlspecialchars($p['peso_kg']) ?> kg</td>
                            <td>
                                <form method="post" class="d-flex gap-2">
                                    <input type="hidden" name="pedido_id" value="<?= (int) $p['id'] ?>">
                                    <select name="transportadora" class="form-select form-select-sm" required>
                                        <option value="">Seleccionar</option>
                                        <?php foreach (array_keys($transportadoras) as $nombre): ?>
                                            <option value="<?= htmlspecialchars($nombre) ?>"><?= htmlspecialchars($nombre) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm text-nowrap">Generar guía</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <form method="get" action="guia_pdf.php" target="_blank">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2>Guías generadas</h2>
                        <p class="muted mb-0">Selecciona varias para imprimirlas juntas.</p>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Imprimir seleccionadas</button>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th></th><th>Pedido</th><th>Cliente</th><th>Destino</th><th>Transportadora</th><th>Guía</th><th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$conGuia): ?>
                            <tr><td colspan="7" class="text-center py-4"><p class="muted mb-0">Aún no hay guías generadas.</p></td></tr>
                        <?php else: ?>
                            <?php foreach ($conGuia as $p): ?>
                            <tr>
                                <td><input type="checkbox" class="form-check-input" name="ids[]" value="<?= (int) $p['id'] ?>"></td>
                                <td>#<?= (int) $p['id'] ?></td>
                                <td><?= htmlspecialchars($p['cliente']) ?></td>
                                <td><?= htmlspecialchars($p['destino']) ?></td>
                                <td><?= htmlspecialchars($p['transportadora'] ?? '') ?></td>
                                <td><?= htmlspecialchars($p['guia']) ?></td>
                                <td><a class="btn btn-outline-primary btn-sm" target="_blank" href="guia_pdf.php?ids[]=<?= (int) $p['id'] ?>">Ver guía</a></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </section>
    </main>
</div>
</body>
</html>