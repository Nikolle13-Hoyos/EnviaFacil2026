
<?php
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: clientes.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: clientes.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Eliminar primero los pedidos del cliente.
    $stmt = $pdo->prepare(
        "DELETE FROM pedidos WHERE cliente_id = ?"
    );
    $stmt->execute([$id]);

    // Después, eliminar el cliente.
    $stmt = $pdo->prepare(
        "DELETE FROM clientes WHERE id = ?"
    );
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        throw new RuntimeException('El cliente no existe.');
    }

    $pdo->commit();

    header('Location: clientes.php');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    http_response_code(500);
    echo 'No se pudo eliminar el cliente y sus pedidos asociados.';
}
