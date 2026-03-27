<?php
header('Content-Type: application/json');
require_once '../includes/funcoes.php';
$conn = conectar();

$lojaId = (int)($_POST['loja_id'] ?? 0);
if ($lojaId <= 0 || empty($_FILES['foto'])) {
  echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos']);
  exit;
}

// Validações
$permitidos = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($_FILES['foto']['type'], $permitidos)) {
  echo json_encode(['sucesso' => false, 'mensagem' => 'Formato não suportado']);
  exit;
}
if ($_FILES['foto']['size'] > 8 * 1024 * 1024) {
  echo json_encode(['sucesso' => false, 'mensagem' => 'Arquivo muito grande']);
  exit;
}

// Pasta da loja
$baseDir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/lojas/$lojaId/";
if (!is_dir($baseDir)) mkdir($baseDir, 0755, true);

// Nome único
$ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
$nome = "loja_{$lojaId}_" . time() . "_" . mt_rand(1000,9999) . "." . $ext;
$dest = $baseDir . $nome;

if (!move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
  echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao salvar arquivo']);
  exit;
}

$url = "/uploads/lojas/$lojaId/$nome";

// Grava no banco
$stmt = $conn->prepare("INSERT INTO loja_fotos (loja_id, caminho_foto, aprovado) VALUES (?, ?, 1)");
$stmt->bind_param("is", $lojaId, $url);
$stmt->execute();
$stmt->close();

echo json_encode(['sucesso' => true, 'caminho' => $url]);
