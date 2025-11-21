<?php
require_once '../dados/conexao.php';

$id = intval($_POST['id'] ?? 0);

// Valores padrão
$setor = 'Gerência';
$nova_loja = 1; // ID da loja padrão
$responsavel_id = 22; // ID do gestor padrão

if ($id <= 0) {
  echo "<p style='color:red;'>❌ ID inválido para reativação.</p>";
  exit;
}

$stmt = $conn->prepare("
  UPDATE inventario
  SET baixa = NULL,
      motivo_baixa = NULL,
      data_baixa = NULL,
      loja_id = ?,
      setor = ?,
      responsavel_id = ?
  WHERE id = ?
");
$stmt->bind_param("isii", $nova_loja, $setor, $responsavel_id, $id);

if ($stmt->execute()) {
  echo "<script>alert('♻️ Item reativado com sucesso.'); window.location.href='inventario.php';</script>";
} else {
  echo "<p style='color:red;'>❌ Erro ao reativar item: " . $stmt->error . "</p>";
}
