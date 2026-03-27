<?php
header('Content-Type: application/json');
require_once '../includes/funcoes.php';
$conn = conectar();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  echo json_encode(['erro' => true, 'mensagem' => 'ID inválido']);
  exit;
}

// Dados da loja
$stmt = $conn->prepare("SELECT nome, foto_fachada FROM lojas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$loja = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fotos extras
$stmt = $conn->prepare("SELECT caminho_foto FROM loja_fotos WHERE loja_id = ? AND aprovado = 1 ORDER BY id DESC");
$stmt->bind_param("i", $id);
$stmt->execute();
$fotos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode([
  'nome' => $loja['nome'] ?? 'Loja',
  'foto_fachada' => $loja['foto_fachada'] ?? null,
  'imagens' => array_column($fotos, 'caminho_foto')
]);
