
<?php
require_once __DIR__ . '/config/db.php';

$stmt = $pdo->query(
    "SELECT id, nombre, telefono, email, direccion, ciudad, departamento, created_at
     FROM clientes
     ORDER BY id DESC"
);

$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clientes | Gestor de Envíos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="simple-page">
<div class="container py-5">

    <a href="index.php" class="text-decoration-none">
        ← Volver
    </a>

    <h1 class="mt-4">Mis clientes</h1>
    <p>Consulta, edita o elimina los clientes</p>

    <div class="table-responsive mt-4">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
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
                    <td colspan="8" class="text-center">
                        Todavía no hay clientes registrados.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($clientes as $cliente): ?>
                    <tr>
                        <td><?= (int) $cliente['id'] ?></td>
                        <td><?= htmlspecialchars($cliente['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($cliente['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($cliente['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($cliente['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($cliente['ciudad'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($cliente['departamento'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <a
                                    href="editar_cliente.php?id=<?= (int) $cliente['id'] ?>"
                                    class="btn btn-warning btn-sm">
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
</div>
</body>
</html>
