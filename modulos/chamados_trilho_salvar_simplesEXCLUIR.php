<?php
session_start();

require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// Verifica login
if (!isset($_SESSION['cpf'])) {
    header("Location: /login.php");
    exit;
}

$tipo             = $_POST['tipo'] ?? 'item';
$solicitante_id   = $_POST['solicitante_id'];
$solicitado_id    = $_POST['solicitado_id'];
$loja_origem_id   = $_POST['loja_origem_id'];
$loja_destino_id  = $_POST['loja_destino_id'];
$descricao        = $_POST['descricao'];
$quantidade       = intval($_POST['quantidade'] ?? 1);
$observacoes      = $_POST['observacoes'] ?? '';

// Gerar protocolo
$protocolo = gerarProtocoloTrilho($conn);

// Inserir
$sql = "
    INSERT INTO chamados_trilho
    (protocolo, tipo, status, data_criacao, loja_origem_id, loja_destino_id,
     solicitante_id, solicitado_id, descricao, quantidade, observacoes)
    VALUES
    (?, ?, 'aberto', NOW(), ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssiiissis",
    $protocolo,
    $tipo,
    $loja_origem_id,
    $loja_destino_id,
    $solicitante_id,
    $solicitado_id,
    $descricao,
    $quantidade,
    $observacoes
);

if ($stmt->execute()) {
    setFlash("Protocolo criado com sucesso!", "success");
} else {
    setFlash("Erro ao criar protocolo: " . $stmt->error, "error");
}

header("Location: chamados_trilho.php");
exit;
