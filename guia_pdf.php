<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/db.php';

$ids = array_filter(array_map('intval', (array) ($_GET['ids'] ?? [])));

if (!$ids) {
    header('Location: guias.php');
    exit;
}

$marcas = implode(',', array_fill(0, count($ids), '?'));

$stmt = $pdo->prepare(
    "SELECT p.*, c.nombre AS cliente, c.telefono, c.direccion,
            c.ciudad, c.departamento
     FROM pedidos p
     INNER JOIN clientes c ON c.id = p.cliente_id
     WHERE p.id IN ($marcas) AND p.guia IS NOT NULL AND p.guia <> ''
     ORDER BY p.id"
);
$stmt->execute(array_values($ids));
$guias = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$guias) {
    header('Location: guias.php');
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Guías de envío | EnviaFácil</title>
    <link rel="icon" type="image/png" href="assets/img/logofondo.png">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f1f5; margin: 0; color: #182038; }
        .barra { text-align: center; padding: 16px; }
        .barra button, .barra a {
            font-size: 15px; padding: 10px 18px; border-radius: 8px; border: 0;
            background: #4b58df; color: white; cursor: pointer; text-decoration: none; margin: 0 4px;
        }
        .barra a { background: #6f7789; }
        .guia {
            width: 15cm; margin: 0 auto 20px; background: white; border: 2px solid #182038;
            padding: 22px; box-sizing: border-box; page-break-after: always;
        }
        .encabezado { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #182038; padding-bottom: 12px; }
        .marca { font-size: 24px; font-weight: 800; }
        .marca span { color: #4b58df; }
        .transportadora { font-size: 20px; font-weight: 700; }
        .numero { text-align: center; padding: 16px 0; border-bottom: 1px solid #ccc; }
        .numero strong { display: block; font-size: 26px; letter-spacing: 2px; }
        .numero svg { max-width: 100%; }
        .bloque { padding: 12px 0; border-bottom: 1px solid #ccc; }
        .bloque small { display: block; color: #6f7789; text-transform: uppercase; font-size: 11px; margin-bottom: 4px; }
        .bloque p { margin: 2px 0; color: #182038; font-size: 15px; }
        .destino-grande { font-size: 22px; font-weight: 700; }
        .datos { display: flex; gap: 30px; padding-top: 12px; }
        .datos div { font-size: 14px; }
        .datos small { display: block; color: #6f7789; font-size: 11px; text-transform: uppercase; }
        @media print {
            body { background: white; }
            .barra { display: none; }
            .guia { margin: 0 auto; border-width: 2px; }
            @page { margin: 1cm; }
        }
    </style>
</head>
<body>

<div class="barra">
    <button onclick="window.print()">Imprimir / Guardar como PDF</button>
    <a href="guias.php">Volver</a>
</div>

<?php foreach ($guias as $g): ?>
<div class="guia">
    <div class="encabezado">
        <div class="marca"><span>Envia</span>Fácil</div>
        <div class="transportadora"><?= htmlspecialchars($g['transportadora'] ?? '') ?></div>
    </div>

    <div class="numero">
        <strong><?= htmlspecialchars($g['guia']) ?></strong>
        <svg class="codigo" data-valor="<?= htmlspecialchars($g['guia']) ?>"></svg>
    </div>

    <div class="bloque">
        <small>Remitente</small>
        <p><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></p>
        <p>Ciudad de origen: <?= htmlspecialchars($g['origen']) ?></p>
    </div>

    <div class="bloque">
        <small>Destinatario</small>
        <p class="destino-grande"><?= htmlspecialchars($g['cliente']) ?></p>
        <p><?= htmlspecialchars($g['direccion'] ?? '') ?></p>
        <p><?= htmlspecialchars($g['ciudad'] ?? '') ?><?= !empty($g['departamento']) ? ', ' . htmlspecialchars($g['departamento']) : '' ?></p>
        <?php if (!empty($g['telefono'])): ?>
            <p>Tel: <?= htmlspecialchars($g['telefono']) ?></p>
        <?php endif; ?>
    </div>

    <div class="datos">
        <div><small>Pedido</small>#<?= (int) $g['id'] ?></div>
        <div><small>Peso</small><?= htmlspecialchars($g['peso_kg']) ?> kg</div>
        <div><small>Valor declarado</small>$<?= number_format((float) $g['valor_declarado'], 0, ',', '.') ?></div>
        <div><small>Fecha</small><?= htmlspecialchars(date('d/m/Y', strtotime($g['created_at']))) ?></div>
    </div>
</div>
<?php endforeach; ?>

<script>
    document.querySelectorAll('.codigo').forEach(function (el) {
        JsBarcode(el, el.dataset.valor, { format: 'CODE128', height: 60, displayValue: false });
    });
</script>

</body>
</html>
