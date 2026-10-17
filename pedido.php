
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
    <title>Nuevo pedido</title>
    <link rel="icon" type="image/png" href="assets/img/logofondo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="simple-page">
<div class="container py-5">
    <a href="index.php" class="text-decoration-none">← Volver</a>

    <div class="form-card mt-3">
        <h1>Crear pedido</h1>
        <p class="text-secondary">
            Registra los datos del cliente y del envío.
            Los campos marcados con * son obligatorios.
        </p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre del cliente *</label>
                <input required name="nombre" class="form-control"
                       value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Teléfono</label>
                <input required name="telefono" type="tel" class="form-control"
                       value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Correo electrónico</label>
                <input required name="email" type="email" class="form-control"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Ciudad de origen *</label>
                <input required name="origen" class="form-control"
                       value="<?= htmlspecialchars($_POST['origen'] ?? '') ?>">
            </div>

            

            <div class="col-md-6">
                <label class="form-label">Departamento de destino</label>
                <input required name="departamento" class="form-control"
                       value="<?= htmlspecialchars($_POST['departamento'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Ciudad de destino *</label>
                <input required name="ciudad" class="form-control"
                       placeholder="Ej. Bogotá"
                       value="<?= htmlspecialchars($_POST['ciudad'] ?? '') ?>">
            </div>

            <div class="col-12">
                <label class="form-label">Dirección de entrega *</label>
                <input required name="direccion" class="form-control"
                       value="<?= htmlspecialchars($_POST['direccion'] ?? '') ?>">
            </div>


            <div class="col-md-6">
                <label class="form-label">Peso del paquete (kg) *</label>
                <input required type="number" step="0.01" min="0.01"
                       name="peso" class="form-control"
                       value="<?= htmlspecialchars($_POST['peso'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Valor declarado (COP)</label>
                <input required type="number" step="100" min="0"
                       name="valor" class="form-control"
                       value="<?= htmlspecialchars($_POST['valor'] ?? '0') ?>">
            </div>

            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary px-4">
                    Guardar y cotizar
                </button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
