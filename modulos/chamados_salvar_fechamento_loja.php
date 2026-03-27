<?php
session_start();
require_once '../dados/conexao.php';
require_once '../includes/funcoes.php';

header('Content-Type: application/json; charset=utf-8');

$conn = conectar();

$idChamado = intval($_POST['id'] ?? 0);
$solucao   = trim($_POST['solucao'] ?? '');

$cargo  = strtolower($_SESSION['cargo'] ?? '');
$cpf    = $_SESSION['cpf'] ?? '';
$lojaId = intval($_SESSION['loja'] ?? 0);

// ===============================
// VALIDAR ACESSO
// ===============================
$temAcesso = in_array($cargo, ['gerente', 'subgerente'])
             || temAcesso($conn, $cpf, 'acesso_painel_loja');

if (!$temAcesso) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

if ($idChamado <= 0 || strlen($solucao) < 3) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

// ===============================
// BUSCAR CHAMADO
// ===============================
$stmt = $conn->prepare("
    SELECT id, loja_destino, status
    FROM chamados
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $idChamado);
$stmt->execute();
$ch = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ch) {
    echo json_encode(['success' => false, 'message' => 'Chamado não encontrado.']);
    exit;
}

if (intval($ch['loja_destino']) !== $lojaId) {
    echo json_encode(['success' => false, 'message' => 'Este chamado não pertence à sua loja.']);
    exit;
}

$statusNorm = strtolower(trim($ch['status']));
if ($statusNorm === 'aguardando avaliacao' || $statusNorm === 'encerrado') {
    echo json_encode(['success' => false, 'message' => 'Este chamado já foi finalizado.']);
    exit;
}

// ===============================
// FECHAR CHAMADO (LOJA)
// ===============================
$conn->begin_transaction();

try {
    // Registrar resposta
    $stmtIns = $conn->prepare("
        INSERT INTO respostas_chamados (chamado_id, resposta, respondido_por, tipo, data)
        VALUES (?, ?, ?, 'fechamento', NOW())
    ");
    $idFunc = intval($_SESSION['funcionario_id'] ?? 0);
    $stmtIns->bind_param("isi", $idChamado, $solucao, $idFunc);
    $stmtIns->execute();
    $stmtIns->close();

    // Atualizar status
    $novoStatus = 'aguardando avaliacao';

    $stmtUpd = $conn->prepare("
        UPDATE chamados
        SET status = ?, data_solucao = NOW(), responsavel_id = ?
        WHERE id = ?
    ");
    $stmtUpd->bind_param("sii", $novoStatus, $idFunc, $idChamado);
    $stmtUpd->execute();
    $stmtUpd->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Chamado fechado com sucesso. Agora aguardando avaliação do solicitante.'
    ]);
    exit;

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Erro ao fechar chamado.']);
    exit;
}
