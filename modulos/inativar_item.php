<?php
require_once '../dados/conexao.php';
$conn = conectar();

$id = intval($_POST['id'] ?? 0);
$motivo = trim($_POST['motivo'] ?? ''); // campo correto

// Validação
if ($id <= 0 || $motivo === '') {
  echo "<p style='color:red;'>❌ Dados inválidos para registrar baixa.</p>";
  echo '<a href="inventario.php">🔙 Voltar</a>';
  exit;
}

// Atualiza os campos de baixa
$stmt = $conn->prepare("
  UPDATE inventario
  SET 
    baixa = CURDATE(),
    data_baixa = CURDATE(),
    motivo_baixa = ?
  WHERE id = ?
");

$stmt->bind_param("si", $motivo, $id);

if ($stmt->execute()) {
  echo "<script>
          alert('🗑️ Baixa registrada com sucesso.');
          window.location.href = 'itens_inativos.php';
        </script>";
} else {
  echo "<p style='color:red;'>❌ Erro ao registrar baixa: " . $stmt->error . "</p>";
}
