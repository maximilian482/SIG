<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

header('Content-Type: application/json; charset=utf-8');

$remetenteId    = $_SESSION['funcionario_id'] ?? 0;
$destinatarioId = (int)($_POST['funcionario_id'] ?? 0);
$conteudo       = trim($_POST['mensagem'] ?? '');

if ($remetenteId <= 0 || $destinatarioId <= 0 || $conteudo === '') {
  echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos']);
  exit;
}

// Upload de arquivo
$arquivoPath = null;
if (!empty($_FILES['arquivo']['name'])) {
  $nomeArquivo = time() . '_' . basename($_FILES['arquivo']['name']);
  $destino = __DIR__ . '/uploads/mensagens/' . $nomeArquivo;

  if (!is_dir(__DIR__ . '/uploads/mensagens')) {
    mkdir(__DIR__ . '/uploads/mensagens', 0777, true);
  }

  if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $destino)) {
    $arquivoPath = '/perfil/uploads/mensagens/' . $nomeArquivo;
  }
}

$stmt = $conn->prepare("
  INSERT INTO mensagens (remetente_id, destinatario_id, conteudo, lida, data, arquivo)
  VALUES (?, ?, ?, 0, NOW(), ?)
");
$stmt->bind_param("iiss", $remetenteId, $destinatarioId, $conteudo, $arquivoPath);

if ($stmt->execute()) {
  echo json_encode(['sucesso' => true]);
} else {
  echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar']);
}
$stmt->close();
