<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();
date_default_timezone_set('America/Sao_Paulo');

$id           = intval($_POST['id'] ?? 0);
$solucao      = trim($_POST['solucao'] ?? '');
$responsavelId = intval($_SESSION['funcionario_id'] ?? 0);

if ($id <= 0 || empty($solucao)) {
  echo '❌ Dados inválidos.';
  exit;
}

// Atualiza chamado: grava solução, responsável e muda status para "aguardando avaliacao"
$stmt = $conn->prepare("
  UPDATE chamados
     SET status = 'aguardando avaliação',
         solucao = ?,
         responsavel_id = ?,
         data_avaliacao = NOW()
   WHERE id = ?
");
$stmt->bind_param("sii", $solucao, $responsavelId, $id);

if ($stmt->execute()) {
  echo '✅ Chamado encerrado. Agora aguardando avaliação do solicitante.';
} else {
  echo '❌ Erro ao encerrar chamado.';
}
