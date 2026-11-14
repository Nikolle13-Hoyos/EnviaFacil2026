<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/db.php';

$transportadoras = ['Servientrega', 'Inter Rapidísimo', 'Coordinadora', 'Envia'];

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
    <style>
        .modal-notif { position: fixed; inset: 0; background: rgba(0, 0, 0, .5);
                       display: none; align-items: center; justify-content: center; z-index: 9999; }
        .modal-notif-contenido { background: #fff; padding: 20px; border-radius: 10px;
                                 width: 90%; max-width: 420px; position: relative; }
        .cerrar-notif { position: absolute; top: 8px; right: 14px; font-size: 24px; cursor: pointer; }
        .burbuja { background: #dcf8c6; padding: 12px; border-radius: 10px;
                   white-space: pre-line; margin: 10px 0; font-size: 14px; }
        .burbuja.correo { background: #f1f1f1; border: 1px solid #ddd; }
    </style>
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

        <div id="aviso" class="alert d-none" role="alert"></div>

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
                                <div class="d-flex gap-2">
                                    <select class="form-select form-select-sm">
                                        <option value="">Seleccionar</option>
                                        <?php foreach ($transportadoras as $nombre): ?>
                                            <option value="<?= htmlspecialchars($nombre) ?>"><?= htmlspecialchars($nombre) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-primary btn-sm text-nowrap btn-generar" data-pedido="<?= (int) $p['id'] ?>">Generar guía</button>
                                </div>
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
                            <th></th><th>Pedido</th><th>Cliente</th><th>Destino</th><th>Transportadora</th><th>Guía</th><th></th><th>Notificar</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$conGuia): ?>
                            <tr><td colspan="8" class="text-center py-4"><p class="muted mb-0">Aún no hay guías generadas.</p></td></tr>
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
                                <td>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-success btn-sm" onclick="notificar(<?= (int) $p['id'] ?>, 'whatsapp')">WhatsApp</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="notificar(<?= (int) $p['id'] ?>, 'correo')">Correo</button>
                                    </div>
                                </td>
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

<div id="modalNotif" class="modal-notif">
    <div class="modal-notif-contenido">
        <span class="cerrar-notif" onclick="cerrarNotif()">&times;</span>
        <h5 id="notifTitulo"></h5>
        <p id="notifPara" class="mb-1"></p>
        <p id="notifAsunto" class="mb-1"></p>
        <div id="notifBurbuja" class="burbuja"></div>
        <br><br>
        <a id="notifLinkWa" href="#" target="_blank" class="btn btn-success btn-sm" style="display:none;">Abrir en WhatsApp</a>
    </div>
</div>

<script>
    const aviso = document.getElementById('aviso');

    function mostrarAviso(tipo, texto) {
        aviso.className = 'alert alert-' + tipo;
        aviso.textContent = texto;
    }

    document.querySelectorAll('.btn-generar').forEach(function (boton) {
        boton.addEventListener('click', async function () {
            const fila = boton.closest('tr');
            const transportadora = fila.querySelector('select').value;

            if (!transportadora) {
                mostrarAviso('warning', 'Selecciona una transportadora para el pedido #' + boton.dataset.pedido + '.');
                return;
            }

            boton.disabled = true;
            boton.textContent = 'Generando...';

            try {
                const respuesta = await fetch('api/generar_guia.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        pedido_id: Number(boton.dataset.pedido),
                        transportadora: transportadora
                    })
                });

                const datos = await respuesta.json();

                if (respuesta.status === 401) {
                    window.location.href = 'login.php';
                    return;
                }

                if (!datos.ok) {
                    throw new Error(datos.error || 'No se pudo generar la guía.');
                }

                mostrarAviso('success', 'Guía generada: ' + datos.guia);
                setTimeout(function () { window.location.reload(); }, 1200);
            } catch (error) {
                mostrarAviso('danger', error.message);
                boton.disabled = false;
                boton.textContent = 'Generar guía';
            }
        });
    });

    async function notificar(pedidoId, canal) {
        try {
            const respuesta = await fetch('api/notificar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ pedido_id: pedidoId, canal: canal })
            });

                        const texto = await respuesta.text();
            let r;
            try {
                r = JSON.parse(texto);
            } catch (e) {
                mostrarAviso('danger', 'Respuesta del servidor: ' + texto.substring(0, 400));
                return;
            }

            if (!r.ok) {
                mostrarAviso('danger', r.error || 'No se pudo generar la notificación.');
                return;
            }

            document.getElementById('notifTitulo').textContent =
                canal === 'whatsapp' ? 'Notificación por WhatsApp' : 'Notificación por correo';
            document.getElementById('notifPara').textContent = 'Para: ' + r.destinatario;

            const asunto = document.getElementById('notifAsunto');
            asunto.textContent = r.asunto ? 'Asunto: ' + r.asunto : '';
            asunto.style.display = r.asunto ? 'block' : 'none';

            const burbuja = document.getElementById('notifBurbuja');
            burbuja.textContent = r.mensaje;
            burbuja.className = 'burbuja' + (canal === 'correo' ? ' correo' : '');

            const enlace = document.getElementById('notifLinkWa');
            if (r.enlace_wa) {
                enlace.href = r.enlace_wa;
                enlace.style.display = 'inline-block';
            } else {
                enlace.style.display = 'none';
            }

            document.getElementById('modalNotif').style.display = 'flex';
        } catch (error) {
            mostrarAviso('danger', 'Error de conexión con el servidor.');
        }
    }

    function cerrarNotif() {
        document.getElementById('modalNotif').style.display = 'none';
    }
</script>

</body>
</html>