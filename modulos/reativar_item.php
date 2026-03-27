<?php
require_once '../dados/conexao.php';
$conn = conectar();

$id = intval($_POST['id'] ?? 0);
$nova_loja = intval($_POST['nova_loja'] ?? 0);

// Valores padrão
$setor = 'Gerência';
$responsavel_id = 22; // Gestor

// Validações
if ($id <= 0) {
  echo "<p style='color:red;'>❌ ID inválido para reativação.</p>";
  exit;
}

if ($nova_loja <= 0) {
  echo "<p style='color:red;'>❌ Loja inválida para reativação.</p>";
  exit;
}

// Atualiza o item
$stmt = $conn->prepare("
  UPDATE inventario
  SET 
      baixa = NULL,
      motivo_baixa = NULL,
      data_baixa = NULL,
      loja_id = ?,
      setor = ?,
      responsavel_id = ?
  WHERE id = ?
");

$stmt->bind_param("isii", $nova_loja, $setor, $responsavel_id, $id);

if ($stmt->execute()) {
  echo "<script>
          alert('♻️ Item reativado com sucesso.');
          window.location.href='inventario.php';
        </script>";
} else {
  echo "<p style='color:red;'>❌ Erro ao reativar item: " . $stmt->error . "</p>";
}
