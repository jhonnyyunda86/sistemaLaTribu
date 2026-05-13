<?php
/**
 * Endpoint AJAX — Detalle de un pedido del cliente
 * Devuelve JSON con los productos del pedido
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] !== 'cliente') {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Pedido.php';
require_once __DIR__ . '/../../models/Reserva.php';

$db          = (new Database())->conectar();
$pedidoModel = new Pedido($db);
$resModel    = new Reserva($db);

$idUsuario = (int)$_SESSION['usuario']['id_usuario'];
$idCliente = $resModel->obtenerIdCliente($idUsuario);
$idPedido  = (int)($_GET['pedido'] ?? 0);

if (!$idCliente || $idPedido <= 0) {
    echo json_encode(['error' => 'Parámetros inválidos']);
    exit;
}

$detalle = $pedidoModel->detallePedido($idPedido, $idCliente);

if (empty($detalle)) {
    echo json_encode(['error' => 'Pedido no encontrado']);
    exit;
}

echo json_encode($detalle);
