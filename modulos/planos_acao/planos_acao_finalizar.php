<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';

$conn = conectar();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'Plano inválido.');
    header("Location: planos_acao_listar.php");
    exit;
}

// Buscar plano
$sql = "SELECT * FROM planos_acao WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$plano = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$plano) {
    setFlash('error', 'Plano não encontrado.');
    header("Location: planos_acao_listar.php");
    exit;
}

// Verificar se já está concluído
if ($plano['status'] === 'concluida') {
    setFlash('info', 'Este plano já está finalizado.');
    header("Location: planos_acao_listar.php");
    exit;
}

// Buscar contadores de tarefas
$sqlT = "
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'concluida' THEN 1 ELSE 0 END) AS concluidas
    FROM tarefas_plano
    WHERE id_plano = ?
";
$stmtT = $conn->prepare($sqlT);
$stmtT->bind_param("i", $id);
$stmtT->execute();
$cont = $stmtT->get_result()->fetch_assoc();
$stmtT->close();

$total = intval($cont['total']);
$concluidas = intval($cont['concluidas']);

$hoje = date('Y-m-d');
$dataFim = $plano['data_fim'] ? date('Y-m-d', strtotime($plano['data_fim'])) : null;

$podeFinalizar = false;

// Caso 1: todas concluídas
if ($total > 0 && $concluidas == $total) {
    $podeFinalizar = true;
}

// Caso 2: prazo acabou
if ($dataFim && $dataFim < $hoje) {
    $podeFinalizar = true;
}

if (!$podeFinalizar) {
    setFlash('error', 'Ainda existem tarefas pendentes. Não é possível finalizar.');
    header("Location: planos_acao_detalhes.php?id=" . $id);
    exit;
}

// Finalizar plano
$sqlF = "UPDATE planos_acao SET status = 'concluida' WHERE id = ?";
$stmtF = $conn->prepare($sqlF);
$stmtF->bind_param("i", $id);
$stmtF->execute();
$stmtF->close();

setFlash('success', 'Plano finalizado com sucesso!');
header("Location: planos_acao_listar.php");
exit;
