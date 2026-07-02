<?php
session_start();
require_once '../dados/conexao.php';
require_once '../includes/funcoes.php';

header('Content-Type: application/json; charset=utf-8');

$conn = conectar();

// ===============================
// PERMISSÃO
// ===============================
$cpf   = $_SESSION['cpf'] ?? '';
$cargo = strtolower($_SESSION['cargo'] ?? '');

$acessoTotal = in_array($cargo, ['super', 'ceo']);

if (!$acessoTotal && !temAcesso($conn, $cpf, "gestao_compras_externas")) {
    echo json_encode(['sucesso' => false, 'erro' => 'Sem permissão para alterar status.']);
    exit;
}

// ===============================
// RECEBER JSON
// ===============================
$input = json_decode(file_get_contents("php://input"), true);

$id     = intval($input['id'] ?? 0);
$status = trim($input['status'] ?? '');
$obs    = trim($input['obs'] ?? '');

if ($id <= 0) {
    echo json_encode(['sucesso' => false, 'erro' => 'ID inválido.']);
    exit;
}

$validos = ['aberto', 'em_compra', 'aguardando_documentos', 'concluido'];

if (!in_array($status, $validos)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Status inválido.']);
    exit;
}

// ===============================
// BUSCAR SOLICITAÇÃO
// ===============================
$sql = "SELECT * FROM compras_externas WHERE id = $id LIMIT 1";
$res = $conn->query($sql);
$compra = $res->fetch_assoc();

if (!$compra) {
    echo json_encode(['sucesso' => false, 'erro' => 'Solicitação não encontrada.']);
    exit;
}

// ===============================
// ATUALIZAR STATUS
// ===============================
$stmt = $conn->prepare("UPDATE compras_externas SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);

if (!$stmt->execute()) {
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao atualizar status.']);
    exit;
}

// ===============================
// SALVAR HISTÓRICO
// ===============================
$descricao = "Status alterado para: " . strtoupper($status);

if ($obs !== '') {
    $descricao .= "\nObs: " . $obs;
}

$stmt2 = $conn->prepare("
    INSERT INTO compras_externas_historico (compra_id, descricao, data)
    VALUES (?, ?, NOW())
");
$stmt2->bind_param("is", $id, $descricao);
$stmt2->execute();

// ===============================
// RETORNO
// ===============================
echo json_encode(['sucesso' => true]);
exit;
