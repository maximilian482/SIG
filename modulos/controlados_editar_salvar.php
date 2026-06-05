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
$vendedor    = trim($_POST['vendedor']);
$produto     = trim($_POST['produto']);
$lote        = trim($_POST['lote']);
$quantidade  = intval($_POST['quantidade']);

// Agora o campo visual é "orcamento", mas salvamos em "cupom"
$orcamento   = trim($_POST['orcamento']);
$cupom       = $orcamento;

// Observação opcional
$observacao  = trim($_POST['observacao'] ?? '');

// O criador do registro NÃO deve ser alterado
$registradoPor = preg_replace('/\D/', '', $_SESSION['cpf']);

$stmt = $conn->prepare("
    UPDATE controlados
    SET 
        data_venda = ?, 
        vendedor   = ?, 
        produto    = ?, 
        lote       = ?, 
        quantidade = ?, 
        cupom      = ?, 
        observacao = ?
    WHERE id = ?
");

$stmt->bind_param(
    "ssssissi",
    $data,
    $vendedor,
    $produto,
    $lote,
    $quantidade,
    $cupom,
    $observacao,
    $id
);

$stmt->execute();

/* ============================
   FLASH MESSAGE PREMIUM
============================ */
$_SESSION['flash'] = [
    'mensagem' => 'Registro atualizado com sucesso!',
    'tipo' => 'success'
];

header("Location: controlados_registros.php?filial=$filial&ok=editado");
exit;
