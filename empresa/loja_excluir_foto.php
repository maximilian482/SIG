<?php
header('Content-Type: application/json');
require_once '../includes/funcoes.php';
$conn = conectar();

$data = json_decode(file_get_contents("php://input"), true);
$lojaId = (int)($data['loja_id'] ?? 0);
$caminho = $data['caminho'] ?? '';

if ($lojaId <= 0 || empty($caminho)) {
  echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos']);
  exit;
}

// Remove do banco
$stmt = $conn->prepare("DELETE FROM loja_fotos WHERE loja_id = ? AND caminho_foto = ?");
$stmt->bind_param("is", $lojaId, $caminho);
$stmt->execute();
$stmt->close();

// Remove do servidor
$abs = $_SERVER['DOCUMENT_ROOT'] . $caminho;
if (file_exists($abs)) unlink($abs);

echo json_encode(['sucesso' => true]);
