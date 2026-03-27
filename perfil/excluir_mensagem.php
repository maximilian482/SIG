<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

header('Content-Type: application/json; charset=utf-8');
if (function_exists('ob_get_level')) { while (ob_get_level()) ob_end_clean(); } // limpa qualquer buffer

$usuarioId = (int)($_SESSION['funcionario_id'] ?? 0);
$id        = (int)($_POST['id'] ?? 0);

if ($usuarioId <= 0 || $id <= 0) {
  echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão inválida ou ID ausente']);
  exit;
}

// Verifica se pertence ao usuário e se está não lida
$check = $conn->prepare("SELECT destinatario_id, lida FROM mensagens WHERE id = ?");
$check->bind_param("i", $id);
$check->execute();
$dados = $check->get_result()->fetch_assoc();
$check->close();

if (!$dados) {
  echo json_encode(['sucesso' => false, 'mensagem' => 'Mensagem não encontrada']);
  exit;
}
if ((int)$dados['destinatario_id'] !== $usuarioId) {
  echo json_encode(['sucesso' => false, 'mensagem' => 'Mensagem não pertence ao usuário logado']);
  exit;
}
$eraNaoLida = (int)$dados['lida'] === 0;

// Exclui
$stmt = $conn->prepare("DELETE FROM mensagens WHERE id = ? AND destinatario_id = ?");
$stmt->bind_param("ii", $id, $usuarioId);
$ok = $stmt->execute();
$rows = $stmt->affected_rows;
$stmt->close();

if ($ok && $rows === 1) {
  echo json_encode(['sucesso' => true, 'eraNaoLida' => $eraNaoLida]);
} else {
  $erro = $conn->error ?: 'Falha ao excluir';
  echo json_encode(['sucesso' => false, 'mensagem' => $erro]);
}
