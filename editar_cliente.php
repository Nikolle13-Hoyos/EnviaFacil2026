
<?php
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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="simple-page">
<div class="container py-5">
    <a href="clientes.php" class="text-decoration-none">
        ← Volver a clientes
    </a>

    <div class="form-card mt-3">
        <h1>Editar cliente</h1>
        <p class="text-secondary">Modifica la información del cliente.</p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre *</label>
                <input required name="nombre" class="form-control"
                       value="<?= e($cliente['nombre']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Teléfono *</label>
                <input required type="tel" name="telefono" class="form-control"
                       value="<?= e($cliente['telefono']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Correo electrónico *</label>
                <input required type="email" name="email" class="form-control"
                       value="<?= e($cliente['email']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Ciudad *</label>
                <input required name="ciudad" class="form-control"
                       value="<?= e($cliente['ciudad']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Departamento *</label>
                <input required name="departamento" class="form-control"
                       value="<?= e($cliente['departamento']) ?>">
            </div>

            <div class="col-12">
                <label class="form-label">Dirección *</label>
                <input required name="direccion" class="form-control"
                       value="<?= e($cliente['direccion']) ?>">
            </div>

            <div class="col-12 d-flex gap-2 justify-content-end">
                <a href="clientes.php" class="btn btn-secondary">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
