<?php
header('Content-Type: application/json');
require_once '../includes/funcoes.php';
$conn = conectar();

$lojaId = (int)($_POST['loja_id'] ?? 0);
if ($lojaId <= 0 || empty($_FILES['fachada'])) {
  echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos']);
  exit;
}

$permitidos = ['image/jpeg','image/png','image/webp'];
if (!in_array($_FILES['fachada']['type'], $permitidos)) {
  echo json_encode(['sucesso' => false, 'mensagem' => 'Formato não suportado']);
  exit;
}

$baseDir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/lojas/$lojaId/";
if (!is_dir($baseDir)) mkdir($baseDir, 0755, true);

$ext = strtolower(pathinfo($_FILES['fachada']['name'], PATHINFO_EXTENSION));
$nome = "fachada_" . time() . "." . $ext;
$dest = $baseDir . $nome;

if (!move_uploaded_file($_FILES['fachada']['tmp_name'], $dest)) {
  echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao salvar arquivo']);
  exit;
}

$url = "/uploads/lojas/$lojaId/$nome";

// Atualiza a coluna foto_fachada
$stmt = $conn->prepare("UPDATE lojas SET foto_fachada = ? WHERE id = ?");
$stmt->bind_param("si", $url, $lojaId);
$stmt->execute();
$stmt->close();

echo json_encode(['sucesso' => true, 'caminho' => $url]);
