<?php
require_once '../dados/conexao.php';

$id = intval($_POST['id'] ?? 0);
$motivo = trim($_POST['motivo_baixa'] ?? '');

if ($id <= 0 || $motivo === '') {
  echo "<p style='color:red;'>❌ Dados inválidos para registrar baixa.</p>";
  echo '<a href="inventario.php">🔙 Voltar</a>';
  exit;
}

// Atualiza os campos de baixa
$stmt = $conn->prepare("
  UPDATE inventario
  SET baixa = CURDATE(), motivo_baixa = ?, data_baixa = CURDATE()
  WHERE id = ?
");
$stmt->bind_param("si", $motivo, $id);

if ($stmt->execute()) {
  echo "<script>alert('🗑️ Baixa registrada com sucesso.'); window.location.href='itens_inativos.php';</script>";
} else {
  echo "<p style='color:red;'>❌ Erro ao registrar baixa: " . $stmt->error . "</p>";
}
