<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/db.php';

$error = '';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: clientes.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT id, nombre, telefono, email, direccion, ciudad, departamento
     FROM clientes
     WHERE id = ?"
);

$stmt->execute([$id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    header('Location: clientes.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $departamento = trim($_POST['departamento'] ?? '');

    // Conservar los datos enviados si hay errores.
    $cliente = array_merge($cliente, [
        'nombre' => $nombre,
        'telefono' => $telefono,
        'email' => $email,
        'direccion' => $direccion,
        'ciudad' => $ciudad,
        'departamento' => $departamento,
    ]);

    if (
        $nombre === '' ||
        $telefono === '' ||
        $email === '' ||
        $direccion === '' ||
        $ciudad === '' ||
        $departamento === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $error = 'Todos los campos son obligatorios y el correo debe ser válido.';

    } else {

        try {

            $stmt = $pdo->prepare(
                "UPDATE clientes
                 SET nombre = ?,
                     telefono = ?,
                     email = ?,
                     direccion = ?,
                     ciudad = ?,
                     departamento = ?
                 WHERE id = ?"
            );

            $stmt->execute([
                $nombre,
                $telefono,
                $email,
                $direccion,
                $ciudad,
                $departamento,
                $id
            ]);

            header('Location: clientes.php');
            exit;

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $error = 'No se pudieron actualizar los datos del cliente.';
        }
    }
}

function e($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>

<!doctype html>
<html lang="es">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Editar cliente | EnviaFácil</title>

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

                <h1>Editar cliente</h1>

                <p>
                    Actualiza la información de tus clientes.
                </p>

            </div>

            <a href="clientes.php" class="btn btn-outline-primary">
                ← Volver a clientes
            </a>

        </div>


        <!-- FORMULARIO -->
        <section class="panel">

            <div class="mb-4">

                <h2>Información del cliente</h2>

                <p class="muted">
                    Modifica los datos necesarios y guarda los cambios.
                </p>

            </div>


            <!-- MENSAJE DE ERROR -->
            <?php if ($error !== ''): ?>

                <div class="alert alert-danger">
                    <?= e($error) ?>
                </div>

            <?php endif; ?>


            <form method="post" class="row g-3">

                <!-- NOMBRE -->
                <div class="col-md-6">

                    <label class="form-label">
                        Nombre completo *
                    </label>

                    <input
                        required
                        name="nombre"
                        class="form-control"
                        placeholder="Nombre del cliente"
                        value="<?= e($cliente['nombre']) ?>">

                </div>


                <!-- TELEFONO -->
                <div class="col-md-6">

                    <label class="form-label">
                        Teléfono *
                    </label>

                    <input
                        required
                        type="tel"
                        name="telefono"
                        class="form-control"
                        placeholder="Número de teléfono"
                        value="<?= e($cliente['telefono']) ?>">

                </div>


                <!-- CORREO -->
                <div class="col-md-6">

                    <label class="form-label">
                        Correo electrónico *
                    </label>

                    <input
                        required
                        type="email"
                        name="email"
                        class="form-control"
                        placeholder="correo@ejemplo.com"
                        value="<?= e($cliente['email']) ?>">

                </div>


                <!-- CIUDAD -->
                <div class="col-md-6">

                    <label class="form-label">
                        Ciudad *
                    </label>

                    <input
                        required
                        name="ciudad"
                        class="form-control"
                        placeholder="Ciudad"
                        value="<?= e($cliente['ciudad']) ?>">

                </div>


                <!-- DEPARTAMENTO -->
                <div class="col-md-6">

                    <label class="form-label">
                        Departamento *
                    </label>

                    <input
                        required
                        name="departamento"
                        class="form-control"
                        placeholder="Departamento"
                        value="<?= e($cliente['departamento']) ?>">

                </div>


                <!-- DIRECCION -->
                <div class="col-12">

                    <label class="form-label">
                        Dirección *
                    </label>

                    <input
                        required
                        name="direccion"
                        class="form-control"
                        placeholder="Dirección completa"
                        value="<?= e($cliente['direccion']) ?>">

                </div>


                <!-- BOTONES -->
                <div class="col-12 d-flex gap-2 justify-content-end mt-4">

                    <a href="clientes.php" class="btn btn-outline-secondary">
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Guardar cambios
                    </button>

                </div>

            </form>

        </section>

    </main>

</div>

</body>
</html>