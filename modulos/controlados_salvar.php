<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: controlados.php");
    exit;
}

$filial         = $_POST['filial'];
$data           = $_POST['data_venda'];

$codigoProduto  = trim($_POST['codigo_produto']);
$produto        = trim($_POST['produto']);

// Agora o campo visual é "orcamento", mas salvamos em "cupom"
$orcamento      = trim($_POST['orcamento']); 
$cupom          = $orcamento; // compatibilidade total

$vendedor       = trim($_POST['vendedor']);
$lote           = trim($_POST['lote']);
$quantidade     = intval($_POST['quantidade']);

// Novo campo opcional
$observacao     = trim($_POST['observacao'] ?? '');

$registradoPor  = preg_replace('/\D/', '', $_SESSION['cpf']);
$registradoNome = $_SESSION['usuario'];

$stmt = $conn->prepare("
    INSERT INTO controlados 
    (filial_id, data_venda, codigo_produto, produto, cupom, vendedor, lote, quantidade, registrado_por, registrado_nome, observacao)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "issssssisss",
    $filial,
    $data,
    $codigoProduto,
    $produto,
    $cupom,
    $vendedor,
    $lote,
    $quantidade,
    $registradoPor,
    $registradoNome,
    $observacao
);

$stmt->execute();

/* ============================
   FLASH MESSAGE PREMIUM
============================ */
$_SESSION['flash'] = [
    'mensagem' => 'Registro criado com sucesso!',
    'tipo' => 'success'
];

header("Location: controlados.php?filial=$filial");
exit;
