<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] !== 'mesero') {
    echo json_encode(['error' => 'No autorizado']); exit;
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Pedido.php';

$db          = (new Database())->conectar();
$pedidoModel = new Pedido($db);
$idPedido    = (int)($_GET['pedido'] ?? 0);

if ($idPedido <= 0) { echo json_encode(['error' => 'ID inválido']); exit; }

$detalle = $pedidoModel->detallePedidoMesero($idPedido);
echo json_encode(empty($detalle) ? ['error' => 'Pedido no encontrado'] : $detalle);
