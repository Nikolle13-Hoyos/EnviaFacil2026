<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/db.php';

$stmt = $pdo->query(
    "SELECT id, nombre, telefono, email, direccion, ciudad, departamento, created_at
     FROM clientes
     ORDER BY id DESC"
);

$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalClientes = count($clientes);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Clientes | EnviaFácil</title>

    <link rel="icon" type="image/png" href="assets/img/logofondo.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

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
        <a href="cotizar.php">Cotizaciones</a>
        <a href="guias.php">Guías</a>
        <a class="active" href="clientes.php">Clientes</a>
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
                <h1>Mis clientes</h1>
                <p>Administra y consulta la información de tus clientes.</p>
            </div>

            <a href="index.php" class="btn btn-outline-primary">
                ← Volver al inicio
            </a>

        </div>


        <!-- TARJETA DE TOTAL -->
        <div class="row g-3 mb-4">

            <div class="col-md-4">

                <div class="metric">

                    <small>Total de clientes</small>

                    <strong><?= $totalClientes ?></strong>

                    <span>Clientes registrados</span>

                </div>

            </div>

        </div>


        <!-- PANEL DE CLIENTES -->
        <section class="panel">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <div>
                    <h2>Listado de clientes</h2>

                    <p class="muted">
                        Consulta la información y administra tus clientes.
                    </p>
                </div>

            </div>


            <!-- TABLA -->
            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Teléfono</th>
                            <th>Correo</th>
                            <th>Dirección</th>
                            <th>Ciudad</th>
                            <th>Departamento</th>
                            <th>Acciones</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($clientes)): ?>

                        <tr>
                            <td colspan="8" class="text-center py-4">

                                <p class="mb-0 muted">
                                    Todavía no hay clientes registrados.
                                </p>

                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($clientes as $cliente): ?>

                            <tr>

                                <td>
                                    #<?= (int) $cliente['id'] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($cliente['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($cliente['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($cliente['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($cliente['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($cliente['ciudad'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($cliente['departamento'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>

                                    <div class="d-flex gap-2">

                                        <a
                                            href="editar_cliente.php?id=<?= (int) $cliente['id'] ?>"
                                            class="btn btn-outline-primary btn-sm">

                                            Editar

                                        </a>


                                        <form
                                            action="eliminar_cliente.php"
                                            method="post"
                                            onsubmit="return confirm('¿Estás seguro de que deseas eliminar este cliente?');">

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int) $cliente['id'] ?>">

                                            <button
                                                type="submit"
                                                class="btn btn-danger btn-sm">

                                                Eliminar

                                            </button>

                                        </form>

                                    </div>

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