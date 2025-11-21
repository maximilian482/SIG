<?php
session_start();
require_once '../dados/conexao.php';

// Inicializa conexão
$conn = conectar();
if (!$conn) {
  echo "<p>❌ Falha ao conectar ao banco de dados.</p>";
  echo '<a class="btn" href="funcionarios.php">🔙 Voltar</a>';
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: funcionarios.php');
  exit;
}

// Capturar dados
$id           = intval($_POST['id'] ?? 0);
$loja_id      = intval($_POST['loja'] ?? 0);
$desligamento = trim($_POST['desligamento'] ?? '');

if ($id <= 0 || $loja_id <= 0 || $desligamento === '') {
  echo "<p>❌ Dados incompletos para inativação.</p>";
  echo '<a class="btn" href="funcionarios.php">🔙 Voltar</a>';
  exit;
}

// Atualizar campo de desligamento
$sql = "UPDATE funcionarios SET desligamento = ? WHERE id = ? AND loja_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo "<p>❌ Erro ao preparar atualização: " . $conn->error . "</p>";
  exit;
}

$stmt->bind_param('sii', $desligamento, $id, $loja_id);

if ($stmt->execute()) {
  $stmt->close();
  header('Location: funcionarios.php?inativado=1');
  exit;
} else {
  echo "<p>❌ Erro ao inativar funcionário: " . $stmt->error . "</p>";
}
?>
