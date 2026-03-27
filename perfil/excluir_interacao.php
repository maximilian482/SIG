<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
$funcionarioId = $_SESSION['funcionario_id'] ?? 0;

if ($id <= 0 || $funcionarioId <= 0) {
  echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos.']);
  exit;
}

// Exclui apenas se a interação pertence ao funcionário logado
$stmt = $conn->prepare("DELETE FROM reconhecimentos WHERE id = ? AND funcionario_id = ?");
$stmt->bind_param("ii", $id, $funcionarioId);

if ($stmt->execute()) {
  echo json_encode(['sucesso' => true]);
} else {
  echo json_encode(['sucesso' => false, 'mensagem' => $conn->error]);
}

$stmt->close();
$conn->close();
