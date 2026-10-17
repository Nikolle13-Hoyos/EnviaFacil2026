# Gestor de Envíos Multitransportadora

Starter académico para construir el proyecto del mockup.

## Stack recomendado
- Frontend: HTML + CSS + Bootstrap 5 + JavaScript
- Backend: PHP 8+
- Base de datos: MySQL
- Administración de BD: phpMyAdmin
- API: endpoints PHP propios (`/api/...`)
- En producción: APIs/webhooks reales de las transportadoras

> phpMyAdmin no es la base de datos. Es una interfaz para administrar MySQL.

## Instalación con XAMPP
1. Instala XAMPP y activa Apache + MySQL.
2. Copia esta carpeta a `C:\xampp\htdocs\gestor-envios`.
3. Entra a `http://localhost/phpmyadmin`.
4. Crea/importa la base de datos ejecutando `database.sql`.
5. Revisa `config/db.php` (usuario/contraseña de MySQL).
6. Abre `http://localhost/gestor-envios/`.

## Endpoints de ejemplo
- GET `/api/orders.php` → lista pedidos.
- POST `/api/orders.php` → crea pedido.
- POST `/api/quote.php` → devuelve cotizaciones simuladas.
- GET `/api/tracking.php?guide=786543210` → devuelve estado simulado.

## Siguiente etapa
Reemplazar las cotizaciones simuladas por integraciones autorizadas con APIs de transportadoras y agregar autenticación, roles, generación real de PDF, WhatsApp y webhooks.
