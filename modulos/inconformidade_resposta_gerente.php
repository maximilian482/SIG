<?php
session_start();
require_once '../dados/conexao.php';
date_default_timezone_set('America/Sao_Paulo');

$conn = conectar();

$idFuncionario = $_SESSION['id_funcionario'] ?? 0;
$id      = intval($_POST['id'] ?? 0);
$solucao = trim($_POST['solucao'] ?? '');

if (!$id || !$solucao || !$idFuncionario) {
  echo "❌ Dados incompletos.";
  exit;
}

$stmt = $conn->prepare("
  UPDATE inconformidades
  SET status = 'Aguardando resposta',
      solucao = ?,
      responsavel_id = ?,
      tratamento_data = NOW()
  WHERE id = ?
");
$stmt->bind_param("sii", $solucao, $idFuncionario, $id);

if ($stmt->execute()) {
  echo "✅ Tratamento registrado com sucesso! Status alterado para 'Aguardando resposta'.";
} else {
  echo "❌ Erro ao atualizar: " . $conn->error;
}
