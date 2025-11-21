<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();
date_default_timezone_set('America/Sao_Paulo');

$id            = intval($_POST['id'] ?? 0);
$avaliacao     = trim($_POST['avaliacao'] ?? '');
$justificativa = trim($_POST['justificativa'] ?? '');
$usuarioId     = intval($_SESSION['funcionario_id'] ?? 0);

if ($id <= 0 || ($avaliacao !== 'Sim' && $avaliacao !== 'Não')) {
  echo '❌ Dados inválidos.';
  exit;
}

// Busca chamado para validar solicitante
$stmt = $conn->prepare("SELECT solicitante_id FROM chamados WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$chamado = $stmt->get_result()->fetch_assoc();

if (!$chamado) {
  echo '❌ Chamado não encontrado.';
  exit;
}

if (intval($chamado['solicitante_id']) !== $usuarioId) {
  echo '❌ Você não é o solicitante deste chamado.';
  exit;
}

// Define novo status e justificativa
$novoStatus   = ($avaliacao === 'Sim') ? 'encerrado' : 'reaberto';
$justificativa = ($avaliacao === 'Não') ? $justificativa : null;

// Atualiza chamado
$stmtUpd = $conn->prepare("
  UPDATE chamados
     SET status = ?, avaliacao = ?, justificativa = ?, data_avaliacao = NOW()
   WHERE id = ?
");
$stmtUpd->bind_param("sssi", $novoStatus, $avaliacao, $justificativa, $id);

if ($stmtUpd->execute()) {
  echo ($avaliacao === 'Sim')
    ? '✅ Atendimento aprovado. Chamado encerrado.'
    : '⚠️ Atendimento reprovado. Chamado reaberto.';
} else {
  echo '❌ Erro ao salvar avaliação.';
}
