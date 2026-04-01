<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// ===============================
// VALIDA LOGIN
// ===============================
if (!isset($_SESSION['cpf'])) {
    die("Acesso negado.");
}

$cpf = $_SESSION['cpf'];

// ===============================
// VALIDA PERMISSÃO TRILHO
// ===============================
if (!temAcesso($conn, $cpf, 'trilho_motoboy')) {
    die("Acesso negado.");
}

// ===============================
// RECEBE DADOS
// ===============================
$id = intval($_POST['id'] ?? 0);
$nome = trim($_POST['assinatura_nome'] ?? '');
$assinatura_base64 = $_POST['assinatura_base64'] ?? '';
$observacoes = trim($_POST['observacoes'] ?? '');

if ($id <= 0 || empty($nome)) {
    die("Dados inválidos.");
}

// Escapar campos
$nome = $conn->real_escape_string($nome);
$observacoes = $conn->real_escape_string($observacoes);

// ===============================
// VERIFICA SE O PROTOCOLO EXISTE
// ===============================
$sqlCheck = "SELECT id FROM chamados_trilho WHERE id = {$id}";
$resCheck = $conn->query($sqlCheck);

if ($resCheck->num_rows == 0) {
    die("Protocolo não encontrado.");
}

// ===============================
// SALVAR ARQUIVO DA ASSINATURA
// ===============================
$nomeArquivo = null;

if (!empty($assinatura_base64)) {

    // Remove prefixo base64
    $assinatura_base64 = str_replace("data:image/png;base64,", "", $assinatura_base64);
    $assinatura_base64 = str_replace(" ", "+", $assinatura_base64);

    $binario = base64_decode($assinatura_base64);

    if (!$binario) {
        die("Erro ao processar assinatura.");
    }

    // Criar pasta se não existir
    $pasta = "../uploads/assinaturas";
    if (!is_dir($pasta)) {
        mkdir($pasta, 0777, true);
    }

    $nomeArquivo = "assinatura_trilho_" . $id . "_" . time() . ".png";
    $caminhoFinal = $pasta . "/" . $nomeArquivo;

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
