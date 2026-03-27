<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

$id = intval($_POST['id'] ?? 0);
$nome = trim($_POST['assinatura_nome'] ?? '');
$assinatura_base64 = $_POST['assinatura_base64'] ?? '';
$observacoes = trim($_POST['observacoes'] ?? '');

if ($id <= 0 || empty($nome)) {
    echo "❌ Dados inválidos.";
    exit;
}

$nome = $conn->real_escape_string($nome);

// ===============================
// SALVAR ARQUIVO DA ASSINATURA
// ===============================
$caminhoFinal = null;

if (!empty($assinatura_base64)) {

    // Remove prefixo base64
    $assinatura_base64 = str_replace("data:image/png;base64,", "", $assinatura_base64);
    $assinatura_base64 = str_replace(" ", "+", $assinatura_base64);

    $binario = base64_decode($assinatura_base64);

    if (!$binario) {
        echo "❌ Erro ao processar assinatura.";
        exit;
    }

    // Criar pasta se não existir
    if (!is_dir("../uploads/assinaturas")) {
        mkdir("../uploads/assinaturas", 0777, true);
    }

    $nomeArquivo = "assinatura_trilho_" . $id . "_" . time() . ".png";
    $caminhoFinal = "../uploads/assinaturas/" . $nomeArquivo;

    file_put_contents($caminhoFinal, $binario);
}

// ===============================
// ATUALIZAR BANCO
// ===============================
$stmt = $conn->prepare("
    UPDATE chamados_trilho
    SET 
        status = 'entregue',
        assinatura_nome = ?,
        assinatura_data = NOW(),
        assinatura_path = ?,
        observacoes = ?
    WHERE id = ?
");

$stmt->bind_param("sssi", $nome, $nomeArquivo, $observacoes, $id);
$stmt->execute();

// ===============================
// REDIRECIONAR
// ===============================
header("Location: trilho_motoboy.php");
exit;
