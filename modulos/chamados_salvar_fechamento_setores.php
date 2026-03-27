<?php
session_start();
require_once '../dados/conexao.php';
require_once '../includes/funcoes.php';

header('Content-Type: application/json; charset=utf-8');

$conn = conectar();

// ===============================
// ENTRADAS
// ===============================
$idChamado = intval($_POST['id'] ?? 0);
$solucao   = trim($_POST['solucao'] ?? '');

$idFunc = intval($_SESSION['funcionario_id'] ?? ($_SESSION['id_funcionario'] ?? 0));
$cpf    = preg_replace('/\D+/', '', $_SESSION['cpf'] ?? '');

if ($idChamado <= 0 || strlen($solucao) < 3 || $idFunc <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

// ===============================
// 1) Buscar chamado
// ===============================
$stmt = $conn->prepare("
    SELECT id, setor_destino, status, responsavel_id
    FROM chamados
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $idChamado);
$stmt->execute();
$ch = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ch) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Chamado não encontrado.']);
    exit;
}

$setorDestino = intval($ch['setor_destino']);
$statusNorm   = normalizarStatus($ch['status']);
$responsavelAtual = intval($ch['responsavel_id']);

// ===============================
// 2) Verificar estado atual
// ===============================
if ($statusNorm === 'encerrado') {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Este chamado já está encerrado.']);
    exit;
}

// ===============================
// 3) Permissão: funcionário deve ter acesso ao setor
// ===============================
$setoresUsuario = usuarioTemSetores($conn, $cpf);
$setoresIds = array_map('intval', array_column($setoresUsuario, 'id'));

$temPermissao = in_array($setorDestino, $setoresIds, true);

// Permitir que o responsável atual feche
if (!$temPermissao && $responsavelAtual === $idFunc) {
    $temPermissao = true;
}

if (!$temPermissao) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Você não tem permissão para fechar este chamado.']);
    exit;
}

// ===============================
// 4) Fechar chamado
// ===============================
$conn->begin_transaction();

try {
    // 4.1 Registrar resposta
    $stmtIns = $conn->prepare("
        INSERT INTO respostas_chamados (chamado_id, resposta, respondido_por, tipo, data)
        VALUES (?, ?, ?, 'fechamento', NOW())
    ");
    $stmtIns->bind_param("isi", $idChamado, $solucao, $idFunc);
    $stmtIns->execute();
    $stmtIns->close();

    // 4.2 Atualizar status
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
    error_log("ERRO fechar chamado: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao fechar chamado.']);
    exit;
}
