<?php
session_start();
require_once '../dados/conexao.php';

// Inicializa conexão
$conn = conectar();
if (!$conn) {
  $_SESSION['erros'] = ['❌ Falha ao conectar ao banco de dados.'];
  header('Location: funcionarios.php');
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

$erros = [];

if ($id <= 0 || $loja_id <= 0 || $desligamento === '') {
  $erros[] = '❌ Dados incompletos para inativação.';
}

if (!empty($erros)) {
  $_SESSION['erros'] = $erros;
  header('Location: funcionarios.php');
  exit;
}

// Atualizar campo de desligamento
$sql = "UPDATE funcionarios SET desligamento = ? WHERE id = ? AND loja_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
  $_SESSION['erros'] = ['❌ Erro ao preparar atualização: ' . $conn->error];
  header('Location: funcionarios.php');
  exit;
}

$stmt->bind_param('sii', $desligamento, $id, $loja_id);

if ($stmt->execute()) {
  $stmt->close();
  $_SESSION['sucesso'] = '✅ Funcionário inativado com sucesso.';
  header('Location: funcionarios.php');
  exit;
} else {
  $_SESSION['erros'] = ['❌ Erro ao inativar funcionário: ' . $stmt->error];
  header('Location: funcionarios.php');
  exit;
}
?>
