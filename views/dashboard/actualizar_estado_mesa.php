<?php
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../models/Mesa.php';

$db = (new Database())->conectar();
$mesaModel = new Mesa($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id'];
    $estado = $_POST['estado'];

    if ($mesaModel->actualizarEstado($id, $estado)) {
        echo "ok";
    } else {
        echo "error";
    }
}