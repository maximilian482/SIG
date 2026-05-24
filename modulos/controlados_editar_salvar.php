<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: controlados.php");
    exit;
}

$id          = intval($_POST['id']);
$filial      = intval($_POST['filial']);
$data        = $_POST['data_venda'];
$vendedor    = $_POST['vendedor']; // agora é nome digitado
$produto     = $_POST['produto'];
$lote        = $_POST['lote'];
$quantidade  = intval($_POST['quantidade']);

// PEGA APENAS O PRIMEIRO NOME DO USUÁRIO LOGADO
$registradoPor = explode(" ", trim($_SESSION['usuario']))[0];

$stmt = $conn->prepare("
    UPDATE controlados
    SET data_venda = ?, 
        vendedor   = ?, 
        produto    = ?, 
        lote       = ?, 
        quantidade = ?, 
        registrado_por = ?
    WHERE id = ?
");

$stmt->bind_param("ssssisi", 
    $data, 
    $vendedor, 
    $produto, 
    $lote, 
    $quantidade, 
    $registradoPor, 
    $id
);

$stmt->execute();

header("Location: controlados_registros.php?filial=$filial&ok=editado");
exit;
