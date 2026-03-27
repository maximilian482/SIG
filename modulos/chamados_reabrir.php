<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

header("Content-Type: text/plain; charset=utf-8");

// ===============================
// VALIDAÇÃO DE SESSÃO
// ===============================
if (!isset($_SESSION['usuario'])) {
    echo "❌ Sessão expirada. Faça login novamente.";
    exit;
}

$usuarioId = intval($_SESSION['funcionario_id'] ?? 0);
if ($usuarioId <= 0) {
    echo "❌ Usuário inválido.";
    exit;
}

// ===============================
// RECEBE DADOS
// ===============================
$idChamado    = intval($_POST['id'] ?? 0);
$justificativa = trim($_POST['justificativa'] ?? '');

if ($idChamado <= 0) {
    echo "❌ Chamado inválido.";
    exit;
}

if (strlen($justificativa) < 3) {
    echo "❌ Explique o motivo da reabertura.";
    exit;
}

// ===============================
// VERIFICA SE O CHAMADO EXISTE
// ===============================
$stmt = $conn->prepare("
    SELECT solicitante_id, status 
    FROM chamados 
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $idChamado);
$stmt->execute();
$dados = $stmt->get_result()->fetch_assoc();

if (!$dados) {
    echo "❌ Chamado não encontrado.";
    exit;
}

$solicitanteId = intval($dados['solicitante_id']);
$statusAtual   = normalizarStatus($dados['status'] ?? '');

// ===============================
// PERMISSÃO
// ===============================
if ($solicitanteId !== $usuarioId) {
    echo "❌ Você não pode reabrir este chamado.";
    exit;
}

if ($statusAtual !== 'encerrado') {
    echo "❌ Este chamado não pode ser reaberto agora.";
    exit;
}

// ===============================
// TRANSAÇÃO
// ===============================
$conn->begin_transaction();

try {

    // 1) REGISTRA NO HISTÓRICO
    $stmtHist = $conn->prepare("
        INSERT INTO respostas_chamados (chamado_id, resposta, respondido_por, tipo, data)
        VALUES (?, ?, ?, 'reabertura', NOW())
    ");
    $stmtHist->bind_param("isi", $idChamado, $justificativa, $usuarioId);

    if (!$stmtHist->execute()) {
        throw new Exception("Erro ao registrar histórico: " . $stmtHist->error);
    }

    // 2) ATUALIZA O CHAMADO
    $stmtUpd = $conn->prepare("
        UPDATE chamados 
        SET status = 'reaberto',
            justificativa = NULL,
            avaliacao = NULL,
            nota_estrelas = NULL,
            data_avaliacao = NULL,
            responsavel_id = ?
        WHERE id = ?
    ");
    $stmtUpd->bind_param("ii", $usuarioId, $idChamado);

    if (!$stmtUpd->execute()) {
        throw new Exception("Erro ao atualizar chamado: " . $stmtUpd->error);
    }

    $conn->commit();
    echo "✔️ Chamado reaberto com sucesso!";
    exit;

} catch (Exception $e) {

    $conn->rollback();
    error_log("ERRO REABRIR CHAMADO: " . $e->getMessage());
    echo "❌ Erro ao reabrir chamado. Tente novamente.";
    exit;
}
