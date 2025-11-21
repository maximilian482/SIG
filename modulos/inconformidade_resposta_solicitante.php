<?php
session_start();
require_once '../dados/conexao.php';
date_default_timezone_set('America/Sao_Paulo');

$conn = conectar();

$idFuncionario = $_SESSION['id_funcionario'] ?? 0;
$id       = intval($_POST['id'] ?? 0);
$resposta = trim($_POST['resposta'] ?? '');
$acao     = $_POST['acao'] ?? '';

if (!$id || !$idFuncionario) {
  echo "❌ Dados incompletos.";
  exit;
}

if ($acao === 'encerrar') {
  // Solicitação aprovada → encerra inconformidade
  $stmt = $conn->prepare("
    UPDATE inconformidades
    SET status = 'Encerrado',
        avaliacao = 'Aprovado',
        avaliacao_data = NOW(),
        encerramento_data = NOW(),
        encerrado_por_id = ?
    WHERE id = ?
  ");
  $stmt->bind_param("ii", $idFuncionario, $id);

} elseif ($acao === 'reabrir') {
  // Solicitação não aprovada → reabre inconformidade
  if (!$resposta) {
    echo "❌ Informe a justificativa para reabrir.";
    exit;
  }
  $stmt = $conn->prepare("
    UPDATE inconformidades
    SET status = 'Reaberto',
        avaliacao = 'Não aprovado',
        avaliacao_data = NOW(),
        avaliacao_justificativa = ?,
        reabertura_data = NOW(),
        responsavel_id = ?
    WHERE id = ?
  ");
  $stmt->bind_param("sii", $resposta, $idFuncionario, $id);

} else {
  echo "❌ Ação inválida.";
  exit;
}

if ($stmt->execute()) {
  echo "✅ Resposta registrada com sucesso!";
} else {
  echo "❌ Erro ao atualizar: " . $conn->error;
}
