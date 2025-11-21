<?php
session_start();
require_once '../dados/conexao.php';

// Inicializa conexão
$conn = conectar();
if (!$conn) {
  echo "<p>❌ Falha ao conectar ao banco de dados.</p>";
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: funcionarios.php');
  exit;
}

// Capturar dados do formulário
$id           = intval($_POST['id'] ?? 0);
$lojaOriginal = intval($_POST['loja_original'] ?? 0);
$codigo       = trim($_POST['codigo'] ?? '');
$cc           = trim($_POST['cc'] ?? '');
$nome         = trim($_POST['nome'] ?? '');
$endereco     = trim($_POST['endereco'] ?? '');
$cpf          = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$cargo_id     = intval($_POST['cargo_id'] ?? 0);
$loja_id      = intval($_POST['loja_id'] ?? 0);
$email        = trim($_POST['email'] ?? '');
$contratacao  = $_POST['contratacao'] ?? '';
$nascimento   = $_POST['aniversario'] ?? null; // manter consistência com o formulário
$telefone     = trim($_POST['telefone'] ?? '');

// Atualizar funcionário
$sql = "
  UPDATE funcionarios SET
    codigo = ?, cc = ?, nome = ?, endereco = ?, cpf = ?,
    cargo_id = ?, loja_id = ?, email = ?, contratacao = ?,
    nascimento = ?, telefone = ?
  WHERE id = ? AND loja_id = ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo "<p>❌ Erro ao preparar atualização: " . $conn->error . "</p>";
  exit;
}

$stmt->bind_param(
  'sssssiissssii',
  $codigo, $cc, $nome, $endereco, $cpf,
  $cargo_id, $loja_id, $email, $contratacao,
  $nascimento, $telefone, $id, $lojaOriginal
);

if ($stmt->execute()) {
  $stmt->close();
  header('Location: funcionarios.php?editado=1');
  exit;
} else {
  echo "<p>❌ Erro ao atualizar funcionário: " . $stmt->error . "</p>";
}
?>
