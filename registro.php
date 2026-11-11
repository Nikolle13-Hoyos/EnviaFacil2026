<?php
require_once __DIR__ . '/config/db.php';

$errores = [];
$exito = false;
$nombre = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if ($nombre === '') {
        $errores[] = 'Escribe tu nombre.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Escribe un correo válido.';
    }

    if (strlen($password) < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    }

    if ($password !== $confirmar) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (!$errores) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $errores[] = 'Ya existe una cuenta con ese correo.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, $email, $hash]);
            $exito = true;
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear cuenta - EnviaFácil</title>
    <link rel="icon" type="image/png" href="assets/img/logofondo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container py-5" style="max-width: 460px;">
    <div class="brand text-center mb-4"><span>Envia</span>Fácil</div>

    <div class="form-card">
        <h1 class="h3">Crear cuenta</h1>
        <p>Regístrate para gestionar los envíos de tu tienda.</p>

        <?php if ($exito): ?>
            <div class="alert alert-success">
                Tu cuenta fue creada correctamente.
                <a href="login.php" class="alert-link">Iniciar sesión</a>
            </div>
        <?php endif; ?>

        <?php if ($errores): ?>
            <div class="alert alert-danger">
                <?php foreach ($errores as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!$exito): ?>
        <form method="post" novalidate>
            <div class="mb-3">
                <label class="form-label" for="nombre">Nombre</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">Correo electrónico</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="confirmar">Confirmar contraseña</label>
                <input type="password" class="form-control" id="confirmar" name="confirmar" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Crear cuenta</button>
        </form>

        <p class="text-center mt-3 mb-0">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>