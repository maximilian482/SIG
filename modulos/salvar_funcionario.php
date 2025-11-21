<?php
session_start();
require_once '../dados/conexao.php';

// Inicializa a conexão
$conn = conectar();
if (!$conn) {
  $_SESSION['erros_funcionario'] = ['❌ Falha ao conectar ao banco de dados.'];
  $_SESSION['dados_funcionario'] = $_POST ?? [];
  header('Location: adicionar_funcionario.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: funcionarios.php');
  exit;
}

// Capturar dados do formulário
$codigo       = trim($_POST['codigo'] ?? '');
$cc           = trim($_POST['cc'] ?? '');
$nome         = trim($_POST['nome'] ?? '');
$endereco     = trim($_POST['endereco'] ?? '');
$cpf          = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$cargo_id     = intval($_POST['cargo_id'] ?? 0);
$loja_id      = intval($_POST['loja_id'] ?? 0);
$email        = trim($_POST['email'] ?? '');
$contratacao  = $_POST['contratacao'] ?? '';
$nascimento   = $_POST['aniversario'] ?? null;
$telefone     = trim($_POST['telefone'] ?? '');

// Validações
$erros = [];

if ($codigo !== '' && $cc !== '' && $codigo === $cc) {
  $erros[] = '❌ O código manual não pode ser igual ao código CC.';
}

function existeDuplicado($conn, $campo, $valor) {
  if ($valor === '') return false; // não valida campo vazio
  $sql = "SELECT id FROM funcionarios WHERE $campo = ?";
  $stmt = $conn->prepare($sql);
  if (!$stmt) return false;
  $stmt->bind_param("s", $valor);
  $stmt->execute();
  $stmt->store_result();
  $duplicado = $stmt->num_rows > 0;
  $stmt->close();
  return $duplicado;
}

if (existeDuplicado($conn, 'cpf', $cpf)) {
  $erros[] = '❌ Já existe um funcionário com este CPF.';
}

if ($codigo !== '' && existeDuplicado($conn, 'codigo', $codigo)) {
  $erros[] = '❌ Já existe um funcionário com este Código Manual.';
}

if ($cc !== '' && existeDuplicado($conn, 'cc', $cc)) {
  $erros[] = '❌ Já existe um funcionário com este código CC.';
}

// Se houver erros, redireciona com mensagens
if (!empty($erros)) {
  $_SESSION['erros_funcionario'] = $erros;
  $_SESSION['dados_funcionario'] = $_POST;
  header('Location: adicionar_funcionario.php');
  exit;
}

// Gerar senha padrão = 6 primeiros dígitos do CPF
$senhaPadrao = substr($cpf, 0, 6);
if (strlen($senhaPadrao) < 6) {
  $_SESSION['erros_funcionario'] = ['❌ CPF inválido para gerar senha padrão.'];
  $_SESSION['dados_funcionario'] = $_POST;
  header('Location: adicionar_funcionario.php');
  exit;
}
$hash = password_hash($senhaPadrao, PASSWORD_DEFAULT);

// Inserção no banco
$sql = "
  INSERT INTO funcionarios (
    codigo, cc, nome, endereco, cpf, cargo_id, loja_id,
    email, contratacao, nascimento, telefone, senha, desligamento
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  $_SESSION['erros_funcionario'] = ['❌ Erro ao preparar inserção: ' . $conn->error];
  $_SESSION['dados_funcionario'] = $_POST;
  header('Location: adicionar_funcionario.php');
  exit;
}

$stmt->bind_param(
  'sssssiisssss',
  $codigo, $cc, $nome, $endereco, $cpf, $cargo_id, $loja_id,
  $email, $contratacao, $nascimento, $telefone, $hash
);

if ($stmt->execute()) {
  $stmt->close();
  unset($_SESSION['erros_funcionario'], $_SESSION['dados_funcionario']);
  header('Location: funcionarios.php?sucesso=1');
  exit;
} else {
  $erro = $stmt->error;
  $stmt->close();
  $_SESSION['erros_funcionario'] = ['❌ Erro ao salvar funcionário: ' . $erro];
  $_SESSION['dados_funcionario'] = $_POST;
  header('Location: adicionar_funcionario.php');
  exit;
}
