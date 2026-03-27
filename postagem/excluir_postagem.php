<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/funcoes.php';
$conn = conectar();

$idFuncionario = $_SESSION['id_funcionario'] ?? null;
$postagemId    = $_POST['postagem_id'] ?? null;

if (!$idFuncionario || !$postagemId) {
  echo json_encode(['sucesso' => false, 'erro' => 'Requisição inválida']);
  exit;
}

// Verifica se a postagem pertence ao funcionário
$stmt = $conn->prepare("SELECT funcionario_id FROM postagens WHERE id = ?");
$stmt->bind_param("i", $postagemId);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) {
  echo json_encode(['sucesso' => false, 'erro' => 'Postagem não encontrada']);
  exit;
}

if ($res['funcionario_id'] != $idFuncionario) {
  echo json_encode(['sucesso' => false, 'erro' => 'Permissão negada']);
  exit;
}

// Exclui comentários vinculados
$stmt = $conn->prepare("DELETE FROM comentarios WHERE postagem_id = ?");
$stmt->bind_param("i", $postagemId);
$stmt->execute();

// Exclui a postagem
$stmt = $conn->prepare("DELETE FROM postagens WHERE id = ?");
$stmt->bind_param("i", $postagemId);
if ($stmt->execute()) {
  echo json_encode(['sucesso' => true]);
} else {
  echo json_encode(['sucesso' => false, 'erro' => 'Falha ao excluir postagem']);
}
