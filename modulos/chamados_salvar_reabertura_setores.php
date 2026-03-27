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
$motivo    = trim($_POST['motivo'] ?? '');

$idFunc = intval($_SESSION['funcionario_id'] ?? ($_SESSION['id_funcionario'] ?? 0));
error_log("DEBUG idFunc = " . $idFunc);

$cpf    = preg_replace('/\D+/', '', $_SESSION['cpf'] ?? '');

if ($idChamado <= 0 || strlen($motivo) < 3 || $idFunc <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

// ===============================
// 1) Buscar chamado
// ===============================
$stmt = $conn->prepare("
    SELECT id, setor_destino, status
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

// ===============================
// 2) Verificar permissão
// ===============================
$setoresUsuario = usuarioTemSetores($conn, $cpf);
$setoresIds = array_map('intval', array_column($setoresUsuario, 'id'));

if (!in_array($setorDestino, $setoresIds, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Você não tem permissão para reabrir este chamado.']);
    exit;
}

// ===============================
// 3) Atualizar chamado
// ===============================
$conn->begin_transaction();

try {
    // 3.1 Registrar resposta
    $stmtIns = $conn->prepare("
        INSERT INTO respostas_chamados (chamado_id, resposta, respondido_por, tipo, data)
        VALUES (?, ?, ?, 'reabertura', NOW())
    ");
    $stmtIns->bind_param("isi", $idChamado, $motivo, $idFunc);
    $stmtIns->execute();
    $stmtIns->close();

    // 3.2 Atualizar status + motivo
    $novoStatus = 'reaberto pelo setor';

    $stmtUpd = $conn->prepare("
        UPDATE chamados
        SET status = ?, motivo_reabertura = ?, data_reabertura = NOW()
        WHERE id = ?
    ");
    $stmtUpd->bind_param("ssi", $novoStatus, $motivo, $idChamado);
    $stmtUpd->execute();
    $stmtUpd->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Chamado reaberto com sucesso.'
    ]);
    exit;

} catch (Exception $e) {
    $conn->rollback();
    error_log("ERRO reabrir chamado: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao reabrir chamado.']);
    exit;
}
