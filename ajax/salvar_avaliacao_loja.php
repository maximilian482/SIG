<?php
session_start();

require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

if (!isset($_SESSION['cpf'])) {
    exit('Acesso negado');
}

$avaliadorId = $_SESSION['funcionario_id'];

// ===============================
// VALIDAR CAMPOS PRINCIPAIS
// ===============================
$lojaId = intval($_POST['loja_id'] ?? 0);
$dataAvaliacao = $_POST['data_avaliacao'] ?? '';
$assinatura = trim($_POST['assinatura'] ?? '');

if ($lojaId <= 0) {
    exit('Loja inválida.');
}

if (!$dataAvaliacao) {
    exit('Informe a data da avaliação.');
}

if ($assinatura === '') {
    exit('A assinatura é obrigatória.');
}

$setores = $_POST['setores'] ?? [];

if (empty($setores)) {
    exit('Nenhum setor foi avaliado.');
}

// ===============================
// 1. CRIAR AVALIAÇÃO GERAL
// ===============================
$sql = "INSERT INTO avaliacoes_loja (loja_id, avaliador_id, data_avaliacao, assinatura)
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiss", $lojaId, $avaliadorId, $dataAvaliacao, $assinatura);
$stmt->execute();

$avaliacaoId = $stmt->insert_id;

// ===============================
// 2. SALVAR SETORES
// ===============================
$sqlSetor = "INSERT INTO avaliacoes_setores 
    (avaliacao_id, setor_id, nota, preco, exposicao, limpeza, organizacao, observacao)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmtSetor = $conn->prepare($sqlSetor);

foreach ($setores as $setorId => $dados) {

    $nota = intval($dados['nota'] ?? 0);

    $preco = $dados['preco'] ?? null;
    $exposicao = $dados['exposicao'] ?? null;
    $limpeza = $dados['limpeza'] ?? null;
    $organizacao = $dados['organizacao'] ?? null;

    $observacao = trim($dados['observacao'] ?? '');

    $stmtSetor->bind_param(
        "iiisssss",
        $avaliacaoId,
        $setorId,
        $nota,
        $preco,
        $exposicao,
        $limpeza,
        $organizacao,
        $observacao
    );

    $stmtSetor->execute();
}

echo "Avaliação salva com sucesso!";
