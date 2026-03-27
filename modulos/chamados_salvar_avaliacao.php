<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

header("Content-Type: text/plain; charset=utf-8");

// ===============================
// VERIFICA LOGIN
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
$idChamado     = intval($_POST['id'] ?? 0);
$avaliacao     = trim($_POST['avaliacao'] ?? '');
$nota          = intval($_POST['nota_estrelas'] ?? 0);
$justificativa = trim($_POST['justificativa'] ?? '');

if ($idChamado <= 0) {
    echo "❌ Chamado inválido.";
    exit;
}

if (!in_array($avaliacao, ["Sim", "Não"], true)) {
    echo "❌ Selecione se você foi atendido.";
    exit;
}

if ($avaliacao === "Sim" && ($nota < 1 || $nota > 5)) {
    echo "❌ Selecione uma nota válida.";
    exit;
}

if ($avaliacao === "Não" && mb_strlen($justificativa) < 3) {
    echo "❌ Explique o motivo da não aprovação.";
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
    echo "❌ Você não pode avaliar este chamado.";
    exit;
}

if ($statusAtual !== "aguardando avaliacao") {
    echo "❌ Este chamado não está aguardando avaliação.";
    exit;
}

// ===============================
// DEFINE NOVO STATUS
// ===============================
$novoStatus = ($avaliacao === "Sim") ? "encerrado" : "reaberto";

// ===============================
// TRANSAÇÃO
// ===============================
$conn->begin_transaction();

try {

    // ===============================
    // 1) ATUALIZA O CHAMADO
    // ===============================
    if ($avaliacao === "Sim") {

        // Avaliação positiva
        $stmtUpd = $conn->prepare("
            UPDATE chamados 
            SET avaliacao = ?, 
                nota_estrelas = ?, 
                justificativa = NULL, 
                data_avaliacao = NOW(),
                status = ?
            WHERE id = ?
        ");
        $stmtUpd->bind_param("sisi", $avaliacao, $nota, $novoStatus, $idChamado);

    } else {

        // Avaliação negativa
        $stmtUpd = $conn->prepare("
            UPDATE chamados 
            SET avaliacao = ?, 
                nota_estrelas = ?, 
                justificativa = ?, 
                data_avaliacao = NOW(),
                status = ?
            WHERE id = ?
        ");
        $stmtUpd->bind_param("sissi", $avaliacao, $nota, $justificativa, $novoStatus, $idChamado);
    }

    if (!$stmtUpd->execute()) {
        throw new Exception("Erro ao atualizar chamado: " . $stmtUpd->error);
    }

    // ===============================
    // 2) REGISTRA HISTÓRICO (somente avaliação negativa)
    // ===============================
    if ($avaliacao === "Não") {

        $tipo = 'avaliacao_negativa';

        $stmtHist = $conn->prepare("
            INSERT INTO respostas_chamados (chamado_id, resposta, respondido_por, tipo, data)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmtHist->bind_param("isis", $idChamado, $justificativa, $usuarioId, $tipo);

        if (!$stmtHist->execute()) {
            throw new Exception("Erro ao registrar histórico: " . $stmtHist->error);
        }
    }

    $conn->commit();
    echo "✔️ Avaliação registrada com sucesso!";
    exit;

} catch (Exception $e) {

    $conn->rollback();
    error_log("ERRO salvar_avaliacao: " . $e->getMessage());
    echo "❌ Erro ao salvar avaliação. Tente novamente.";
    exit;
}
