<?php
session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['action'] ?? '';
    $producto_id = $_POST['producto_id'] ?? 0;
    $origen      = $_POST['origen'] ?? 'index.html';

    if (!isset($_SESSION['usuario_id'])) {
        header("Location: login.html?msg=" . urlencode("Debes iniciar sesión para comprar en SEPHORA MX.") . "&type=error");
        exit;
    }

    $usuario_id = $_SESSION['usuario_id'];

    if ($action === 'agregar_carrito' && $producto_id > 0) {
        $stmtProd = $pdo->prepare("SELECT precio FROM productos WHERE id = ?");
        $stmtProd->execute([$producto_id]);
        $producto = $stmtProd->fetch();

        if ($producto) {
            $subtotal = $producto['precio'];
            $iva      = $subtotal * 0.16;
            $total    = $subtotal + $iva;

            try {
                $pdo->beginTransaction();
                $stmtPed = $pdo->prepare("INSERT INTO pedidos (usuario_id, subtotal, iva, total) VALUES (?, ?, ?, ?)");
                $stmtPed->execute([$usuario_id, $subtotal, $iva, $total]);
                $pedido_id = $pdo->lastInsertId();

                $stmtDet = $pdo->prepare("INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, 1, ?)");
                $stmtDet->execute([$pedido_id, $producto_id, $subtotal]);

                $pdo->commit();

                $msg = "¡Compra del Pedido #" . $pedido_id . " registrada en SQL! Total: $" . number_format($total, 2) . " MXN";
                header("Location: " . $origen . "?msg=" . urlencode($msg) . "&type=exito");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                header("Location: " . $origen . "?msg=" . urlencode("Error en SQL: " . $e->getMessage()) . "&type=error");
                exit;
            }
        }
    }
}
?>