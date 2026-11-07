
<?php
require_once __DIR__ . '/config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $departamento = trim($_POST['departamento'] ?? '');
    $origen = trim($_POST['origen'] ?? '');
    $peso = (float)($_POST['peso'] ?? 0);
    $valor = (float)($_POST['valor'] ?? 0);

    if (
        $nombre === '' ||
        $direccion === '' ||
        $ciudad === '' ||
        $origen === '' ||
        $peso <= 0 ||
        $valor < 0 ||
        ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
    ) {

        $error = 'Revisa los campos obligatorios, el peso y el correo electrónico.';

    } else {

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO clientes
                (nombre, telefono, email, direccion, ciudad, departamento)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $nombre,
                $telefono ?: null,
                $email ?: null,
                $direccion,
                $ciudad,
                $departamento ?: null
            ]);

            $clienteId = $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                "INSERT INTO pedidos
                (cliente_id, origen, destino, peso_kg, valor_declarado)
                VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $clienteId,
                $origen,
                $ciudad,
                $peso,
                $valor
            ]);

            $pedidoId = $pdo->lastInsertId();

            $pdo->commit();

            header('Location: cotizar.php?pedido=' . $pedidoId);
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $error = 'No se pudo guardar el pedido. Revisa la estructura de las tablas y la conexión a la base de datos.';
        }
    }
}
?>

<!doctype html>
<html lang="es">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Nuevo pedido | EnviaFácil</title>

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
        <a class="active" href="pedido.php">Pedidos</a>
        <a href="cotizar.php">Cotizaciones</a>
        <a href="pedido.php">Guías</a>
        <a href="clientes.php">Clientes</a>
        <a href="index.php">Historial de envíos</a>
        <a href="index.php">Reportes</a>

    </aside>


    <!-- CONTENIDO PRINCIPAL -->
    <main class="content">

        <!-- ENCABEZADO -->
        <div class="topbar">

            <div>

                <h1>Crear nuevo pedido</h1>

                <p>
                    Registra los datos del cliente y la información del envío.
                </p>

            </div>

            <a href="index.php" class="btn btn-outline-primary">
                ← Volver al inicio
            </a>

        </div>


        <!-- FORMULARIO -->
        <section class="panel">

            <div class="mb-4">

                <h2>Información del envío</h2>

                <p class="muted">
                    Completa los siguientes datos para registrar un nuevo pedido.
                    Los campos marcados con * son obligatorios.
                </p>

            </div>


            <!-- MENSAJE DE ERROR -->
            <?php if ($error !== ''): ?>

                <div class="alert alert-danger">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>

            <?php endif; ?>


            <form method="post" class="row g-3">


                <!-- DATOS DEL CLIENTE -->
                <div class="col-12">

                    <h5 class="mb-1">Datos del cliente</h5>

                    <p class="muted">
                        Información de la persona que recibirá el envío.
                    </p>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Nombre del cliente *
                    </label>

                    <input
                        required
                        name="nombre"
                        class="form-control"
                        placeholder="Nombre completo"
                        value="<?= htmlspecialchars($_POST['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Teléfono
                    </label>

                    <input
                        name="telefono"
                        type="tel"
                        class="form-control"
                        placeholder="Número de teléfono"
                        value="<?= htmlspecialchars($_POST['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Correo electrónico
                    </label>

                    <input
                        name="email"
                        type="email"
                        class="form-control"
                        placeholder="correo@ejemplo.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                </div>


                <!-- DATOS DEL DESTINO -->
                <div class="col-12 mt-4">

                    <h5 class="mb-1">Información del destino</h5>

                    <p class="muted">
                        Indica dónde se entregará el paquete.
                    </p>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Departamento de destino
                    </label>

                    <input
                        name="departamento"
                        class="form-control"
                        placeholder="Ej. Cundinamarca"
                        value="<?= htmlspecialchars($_POST['departamento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Ciudad de destino *
                    </label>

                    <input
                        required
                        name="ciudad"
                        class="form-control"
                        placeholder="Ej. Bogotá"
                        value="<?= htmlspecialchars($_POST['ciudad'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                </div>


                <div class="col-12">

                    <label class="form-label">
                        Dirección de entrega *
                    </label>

                    <input
                        required
                        name="direccion"
                        class="form-control"
                        placeholder="Dirección completa de entrega"
                        value="<?= htmlspecialchars($_POST['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                </div>


                <!-- DATOS DEL PAQUETE -->
                <div class="col-12 mt-4">

                    <h5 class="mb-1">Detalles del paquete</h5>

                    <p class="muted">
                        Completa los datos necesarios para calcular el costo del envío.
                    </p>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Ciudad de origen *
                    </label>

                    <input
                        required
                        name="origen"
                        class="form-control"
                        placeholder="Ej. Medellín"
                        value="<?= htmlspecialchars($_POST['origen'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Peso del paquete (kg) *
                    </label>

                    <input
                        required
                        type="number"
                        step="0.01"
                        min="0.01"
                        name="peso"
                        class="form-control"
                        placeholder="Ej. 2.5"
                        value="<?= htmlspecialchars($_POST['peso'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Valor declarado (COP)
                    </label>

                    <input
                        type="number"
                        step="100"
                        min="0"
                        name="valor"
                        class="form-control"
                        placeholder="Ej. 100000"
                        value="<?= htmlspecialchars($_POST['valor'] ?? '0', ENT_QUOTES, 'UTF-8') ?>">

                </div>


                <!-- BOTONES -->
                <div class="col-12 mt-4">

                    <div class="d-flex justify-content-end gap-2">

                        <a href="index.php" class="btn btn-outline-secondary">
                            Cancelar
                        </a>

                        <button type="submit" class="btn btn-primary px-4">
                            Guardar y cotizar
                        </button>

                    </div>

                </div>

            </form>

        </section>

    </main>

</div>

</body>
</html>
