<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/db.php';

// FUNCION PARA PROTEGER LOS DATOS
function h($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

// FILTROS
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$estado = $_GET['estado'] ?? '';
$aviso = '';

// ESTADOS PERMITIDOS
$estadosPermitidos = [
    'PEDIDO_CREADO',
    'GUIA_GENERADA',
    'EN_TRANSITO',
    'EN_REPARTO',
    'ENTREGADO'
];

// AGREGAR LOS ESTADOS QUE EXISTAN EN LA BASE DE DATOS
// (asi el filtro siempre coincide con los valores reales de la tabla)
$estadosBD = $pdo->query(
    "SELECT DISTINCT estado FROM pedidos WHERE estado IS NOT NULL AND estado <> ''"
)->fetchAll(PDO::FETCH_COLUMN);

foreach ($estadosBD as $estadoBD) {
    if (!in_array($estadoBD, $estadosPermitidos, true)) {
        $estadosPermitidos[] = $estadoBD;
    }
}

// VALIDAR FECHAS
if ($desde !== '') {
    $fecha = DateTime::createFromFormat('!Y-m-d', $desde);
    if (!$fecha || $fecha->format('Y-m-d') !== $desde) {
        $desde = '';
    }
}

if ($hasta !== '') {
    $fecha = DateTime::createFromFormat('!Y-m-d', $hasta);
    if (!$fecha || $fecha->format('Y-m-d') !== $hasta) {
        $hasta = '';
    }
}

// SI LA FECHA INICIAL ES MAYOR QUE LA FINAL, SE INTERCAMBIAN
if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
    [$desde, $hasta] = [$hasta, $desde];
    $aviso = 'La fecha inicial era mayor que la final, por eso se intercambiaron.';
}

// VALIDAR ESTADO
if (!in_array($estado, $estadosPermitidos, true)) {
    $estado = '';
}


// ==================================================
// CONDICIONES SQL PARA TODOS LOS REPORTES
// ==================================================

$where = [];
$params = [];

// FILTRO POR FECHA INICIAL
if ($desde !== '') {
    $where[] = "p.created_at >= ?";
    $params[] = $desde . ' 00:00:00';
}

// FILTRO POR FECHA FINAL
if ($hasta !== '') {
    $fechaLimite = new DateTime($hasta);
    $fechaLimite->modify('+1 day');

    $where[] = "p.created_at < ?";
    $params[] = $fechaLimite->format('Y-m-d') . ' 00:00:00';
}

// FILTRO POR ESTADO
if ($estado !== '') {
    $where[] = "p.estado = ?";
    $params[] = $estado;
}

// CONSTRUIR CONDICION
$whereSQL = $where
    ? 'WHERE ' . implode(' AND ', $where)
    : '';

// FILTROS ACTIVOS (PARA MOSTRARLOS EN PANTALLA)
$filtrosActivos = [];

if ($desde !== '') {
    $filtrosActivos[] = 'Desde ' . $desde;
}

if ($hasta !== '') {
    $filtrosActivos[] = 'Hasta ' . $hasta;
}

if ($estado !== '') {
    $filtrosActivos[] = 'Estado: ' . str_replace('_', ' ', $estado);
}


// ==================================================
// 1. RESUMEN GENERAL
// ==================================================

$sqlResumen = "
    SELECT
        COUNT(*) AS total,

        SUM(CASE
            WHEN p.estado IN (
                'PEDIDO_CREADO',
                'GUIA_GENERADA',
                'EN_TRANSITO',
                'EN_REPARTO'
            )
            THEN 1 ELSE 0
        END) AS en_proceso,

        SUM(CASE
            WHEN p.estado = 'ENTREGADO'
            THEN 1 ELSE 0
        END) AS entregados,

        SUM(CASE
            WHEN p.estado IN (
                'PEDIDO_CREADO',
                'GUIA_GENERADA'
            )
            THEN 1 ELSE 0
        END) AS pendientes

    FROM pedidos p
    $whereSQL
";

$stmt = $pdo->prepare($sqlResumen);
$stmt->execute($params);
$resumen = $stmt->fetch(PDO::FETCH_ASSOC);


// ==================================================
// 2. PEDIDOS POR ESTADO
// ==================================================

$sqlEstados = "
    SELECT
        p.estado,
        COUNT(*) AS cantidad

    FROM pedidos p

    $whereSQL

    GROUP BY p.estado
    ORDER BY cantidad DESC
";

$stmt = $pdo->prepare($sqlEstados);
$stmt->execute($params);
$datosEstados = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==================================================
// 3. ENVIOS POR MES
// ==================================================

$sqlMeses = "
    SELECT
        DATE_FORMAT(p.created_at, '%Y-%m') AS mes,
        COUNT(*) AS cantidad

    FROM pedidos p

    $whereSQL

    GROUP BY mes

    ORDER BY mes
";

$stmt = $pdo->prepare($sqlMeses);
$stmt->execute($params);
$datosMeses = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==================================================
// 4. DESTINOS MAS FRECUENTES
// ==================================================

$sqlDestinos = "
    SELECT
        p.destino,
        COUNT(*) AS cantidad

    FROM pedidos p

    $whereSQL

    GROUP BY p.destino

    ORDER BY cantidad DESC

    LIMIT 5
";

$stmt = $pdo->prepare($sqlDestinos);
$stmt->execute($params);
$datosDestinos = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==================================================
// 5. HISTORIAL DE PEDIDOS
// ==================================================

$sqlPedidos = "
    SELECT
        p.id,
        p.destino,
        p.estado,
        p.transportadora,
        p.guia,
        p.created_at,
        c.nombre AS cliente

    FROM pedidos p

    INNER JOIN clientes c ON c.id = p.cliente_id

    $whereSQL

    ORDER BY p.created_at DESC
";

$stmt = $pdo->prepare($sqlPedidos);
$stmt->execute($params);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==================================================
// 6. PREPARAR DATOS PARA LOS GRAFICOS
// ==================================================

$estadosLabels = [];
$estadosValores = [];

foreach ($datosEstados as $dato) {
    $estadosLabels[] = str_replace('_', ' ', $dato['estado']);
    $estadosValores[] = (int)$dato['cantidad'];
}

$mesesLabels = [];
$mesesValores = [];

foreach ($datosMeses as $dato) {
    $mesesLabels[] = $dato['mes'];
    $mesesValores[] = (int)$dato['cantidad'];
}

$destinosLabels = [];
$destinosValores = [];

foreach ($datosDestinos as $dato) {
    $destinosLabels[] = $dato['destino'];
    $destinosValores[] = (int)$dato['cantidad'];
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Reportes | EnviaFácil</title>

    <link rel="icon" type="image/png" href="assets/img/logo.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="assets/style.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


</head>

<body>

<div class="app">

    <aside class="sidebar">
    <div class="brand">
        <span>Envia</span>Fácil
    </div>

    <div class="small opacity-75 mb-3">
        Crecemos juntos, entrega a entrega.
    </div>

    <a href="index.php">Inicio</a>
    <a href="pedido.php">Pedidos</a>
    <a href="cotizar.php">Cotizaciones</a>
    <a href="guias.php">Guías</a>
    <a href="clientes.php">Clientes</a>
    <a href="index.php">Historial de envíos</a>
    <a class="active" href="reportes.php">Reportes</a>

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

                <h1>Reportes y estadísticas</h1>

                <p>
                    Analiza el rendimiento y comportamiento de tus envíos.
                </p>

            </div>

            <a href="index.php" class="btn btn-outline-primary">

                ← Volver al inicio

            </a>

        </div>


        <!-- TARJETAS DE RESUMEN -->

        <div class="row g-3 mb-4">

            <div class="col-md-6 col-xl-3">

                <div class="metric">

                    <div class="report-icon">📦</div>

                    <small>Total de pedidos</small>

                    <strong><?= (int)($resumen['total'] ?? 0) ?></strong>

                    <span>Pedidos registrados</span>

                </div>

            </div>


            <div class="col-md-6 col-xl-3">

                <div class="metric">

                    <div class="report-icon">🚚</div>

                    <small>En proceso</small>

                    <strong><?= (int)($resumen['en_proceso'] ?? 0) ?></strong>

                    <span>Envíos en movimiento</span>

                </div>

            </div>


            <div class="col-md-6 col-xl-3">

                <div class="metric">

                    <div class="report-icon">✅</div>

                    <small>Entregados</small>

                    <strong><?= (int)($resumen['entregados'] ?? 0) ?></strong>

                    <span>Pedidos completados</span>

                </div>

            </div>


            <div class="col-md-6 col-xl-3">

                <div class="metric">

                    <div class="report-icon">⏳</div>

                    <small>Pendientes</small>

                    <strong><?= (int)($resumen['pendientes'] ?? 0) ?></strong>

                    <span>Pedidos por gestionar</span>

                </div>

            </div>

        </div>


        <!-- FILTROS -->

        <section class="panel report-section">

            <h2 class="mb-2">Filtrar reportes</h2>

            <p class="muted">
                Selecciona un periodo y un estado para consultar los pedidos.
            </p>

            <?php if ($aviso !== ''): ?>

                <div class="alert alert-warning py-2">
                    <?= h($aviso) ?>
                </div>

            <?php endif; ?>

            <form method="GET" action="reportes.php#resultados" class="report-filter">

                <div class="row g-3 align-items-end">

                    <div class="col-md-4">

                        <label class="form-label">
                            Fecha inicial
                        </label>

                        <input
                            type="date"
                            name="desde"
                            class="form-control"
                            value="<?= h($desde) ?>"
                            onchange="this.form.submit()">

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            Fecha final
                        </label>

                        <input
                            type="date"
                            name="hasta"
                            class="form-control"
                            value="<?= h($hasta) ?>"
                            onchange="this.form.submit()">

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            Estado del pedido
                        </label>

                        <select name="estado" class="form-select" onchange="this.form.submit()">

                            <option value="">
                                Todos los estados
                            </option>

                            <?php foreach ($estadosPermitidos as $opcion): ?>

                                <option
                                    value="<?= h($opcion) ?>"
                                    <?= $estado === $opcion ? 'selected' : '' ?>>

                                    <?= h(str_replace('_', ' ', $opcion)) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-12 d-flex justify-content-end gap-2">

                        <a href="reportes.php"
                           class="btn btn-outline-secondary">

                            Limpiar filtros

                        </a>

                        <button type="submit" class="btn btn-primary">

                            Aplicar filtros

                        </button>

                    </div>

                </div>

            </form>



        </section>


        <!-- HISTORIAL DE PEDIDOS -->

        <section class="panel report-section" id="resultados">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <div>

                    <h2>Historial de pedidos</h2>

                    <p class="muted">
                        <?= !empty($filtrosActivos) ? 'Filtros activos: ' . h(implode(' · ', $filtrosActivos)) : 'Mostrando todos los pedidos.' ?>
                    </p>

                </div>

                <span class="badge text-bg-primary">

                    <?= count($pedidos) ?> pedidos

                </span>

            </div>


            <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">

                <table class="table align-middle">

                    <thead>

                        <tr>

                            <th>Pedido</th>

                            <th>Cliente</th>

                            <th>Destino</th>

                            <th>Transportadora</th>

                            <th>Guía</th>

                            <th>Fecha</th>

                            <th>Estado</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($pedidos)): ?>

                        <tr>

                            <td colspan="7" class="text-center py-4">

                                <p class="muted mb-0">
                                    No se encontraron pedidos con estos filtros.
                                </p>

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($pedidos as $p): ?>

                            <tr>

                                <td>
                                    #<?= (int)$p['id'] ?>
                                </td>

                                <td>
                                    <?= h($p['cliente']) ?>
                                </td>

                                <td>
                                    <?= h($p['destino']) ?>
                                </td>

                                <td>
                                    <?= h($p['transportadora'] ?: '-') ?>
                                </td>

                                <td>
                                    <?= h($p['guia'] ?: '-') ?>
                                </td>

                                <td>
                                    <?= h($p['created_at']) ?>
                                </td>

                                <td>

                                    <span class="status status-<?= strtolower($p['estado']) ?>">

                                        <?= h(str_replace('_', ' ', $p['estado'])) ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>



        <!-- GRAFICOS -->

        <div class="row g-3 report-section">

            <div class="col-lg-6">

                <section class="panel h-100">

                    <h2 class="report-title">
                        Pedidos por estado
                    </h2>

                    <div class="report-chart">

                        <?php if (!empty($datosEstados)): ?>

                            <canvas id="graficoEstados"></canvas>

                        <?php else: ?>

                            <div class="report-empty">
                                No hay datos para mostrar.
                            </div>

                        <?php endif; ?>

                    </div>

                </section>

            </div>


            <div class="col-lg-6">

                <section class="panel h-100">

                    <h2 class="report-title">
                        Envíos por mes
                    </h2>

                    <div class="report-chart">

                        <?php if (!empty($datosMeses)): ?>

                            <canvas id="graficoMeses"></canvas>

                        <?php else: ?>

                            <div class="report-empty">
                                No hay datos para mostrar.
                            </div>

                        <?php endif; ?>

                    </div>

                </section>

            </div>

        </div>


        <!-- DESTINOS MAS FRECUENTES -->

        <section class="panel report-section">

            <h2 class="report-title">
                Destinos más frecuentes
            </h2>

            <div class="report-chart">

                <?php if (!empty($datosDestinos)): ?>

                    <canvas id="graficoDestinos"></canvas>

                <?php else: ?>

                    <div class="report-empty">
                        No hay destinos para mostrar.
                    </div>

                <?php endif; ?>

            </div>

        </section>


    </main>

</div>


<!-- ================================================== -->
<!-- GRAFICOS CON CHART.JS -->
<!-- ================================================== -->

<script>

// DATOS ENVIADOS DESDE PHP

const estadosLabels = <?= json_encode($estadosLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

const estadosValores = <?= json_encode($estadosValores) ?>;

const mesesLabels = <?= json_encode($mesesLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

const mesesValores = <?= json_encode($mesesValores) ?>;

const destinosLabels = <?= json_encode($destinosLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

const destinosValores = <?= json_encode($destinosValores) ?>;


// COLORES DE ENVIAFACIL

const colores = [
    '#4b58df',
    '#159b70',
    '#f0a43c',
    '#36a3d9',
    '#8f9aff'
];


// CONFIGURACION GENERAL

Chart.defaults.font.family = 'Inter, Arial, sans-serif';

Chart.defaults.color = '#70798d';


// ==================================================
// GRAFICO 1: PEDIDOS POR ESTADO
// ==================================================

const canvasEstados = document.getElementById('graficoEstados');

if (canvasEstados) {

    new Chart(canvasEstados, {

        type: 'doughnut',

        data: {

            labels: estadosLabels,

            datasets: [{

                data: estadosValores,

                backgroundColor: colores,

                borderWidth: 3,

                borderColor: '#ffffff',

                hoverOffset: 8

            }]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            plugins: {

                legend: {

                    position: 'bottom',

                    labels: {

                        padding: 18,

                        usePointStyle: true

                    }

                }

            }

        }

    });

}


// ==================================================
// GRAFICO 2: ENVIOS POR MES
// ==================================================

const canvasMeses = document.getElementById('graficoMeses');

if (canvasMeses) {

    new Chart(canvasMeses, {

        type: 'bar',

        data: {

            labels: mesesLabels,

            datasets: [{

                label: 'Cantidad de envíos',

                data: mesesValores,

                backgroundColor: '#4b58df',

                borderRadius: 7,

                maxBarThickness: 55

            }]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            scales: {

                y: {

                    beginAtZero: true,

                    ticks: {

                        precision: 0

                    }

                }

            },

            plugins: {

                legend: {

                    display: false

                }

            }

        }

    });

}


// ==================================================
// GRAFICO 3: DESTINOS MAS FRECUENTES
// ==================================================

const canvasDestinos = document.getElementById('graficoDestinos');

if (canvasDestinos) {

    new Chart(canvasDestinos, {

        type: 'bar',

        data: {

            labels: destinosLabels,

            datasets: [{

                label: 'Cantidad de envíos',

                data: destinosValores,

                backgroundColor: '#36a3d9',

                borderRadius: 7,

                maxBarThickness: 45

            }]

        },

        options: {

            indexAxis: 'y',

            responsive: true,

            maintainAspectRatio: false,

            scales: {

                x: {

                    beginAtZero: true,

                    ticks: {

                        precision: 0

                    }

                }

            },

            plugins: {

                legend: {

                    display: false

                }

            }

        }

    });

}

</script>

</body>
</html>