<?php
require_once '../dados/conexao.php';

$id = intval($_POST['id'] ?? 0);
$nova_loja = intval($_POST['nova_loja'] ?? 0);
$tipo = $_POST['tipo'] ?? '';
$descricao = $_POST['descricao'] ?? '';
$fabricante = $_POST['fabricante'] ?? '';
$setor = $_POST['setor'] ?? '';
$responsavel_nome = $_POST['responsavel'] ?? '';
$valor = floatval($_POST['valor'] ?? 0);

// Buscar ID do responsável pelo nome
$responsavel_id = null;
$stmt = $conn->prepare("SELECT id FROM funcionarios WHERE nome = ? LIMIT 1");
$stmt->bind_param("s", $responsavel_nome);
$stmt->execute();
$stmt->bind_result($resp_id);
if ($stmt->fetch()) {
  $responsavel_id = $resp_id;
}
$stmt->close();

// Atualizar item
$stmt = $conn->prepare("
  UPDATE inventario
  SET loja_id = ?, descricao = ?, fabricante = ?, setor = ?, valor = ?, responsavel_id = ?
  WHERE id = ?
");
$stmt->bind_param("isssdii", $nova_loja, $descricao, $fabricante, $setor, $valor, $responsavel_id, $id);

if ($stmt->execute()) {
  echo "<script>alert('✅ Item atualizado com sucesso.'); window.location.href='inventario.php';</script>";
} else {
  echo "<p style='color:red;'>❌ Erro ao atualizar: " . $stmt->error . "</p>";
}
