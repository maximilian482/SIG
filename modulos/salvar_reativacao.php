<?php
session_start();
require_once '../dados/conexao.php';

// Inicializa a conexão
$conn = conectar();
if (!$conn) {
  $_SESSION['alerta'] = '❌ Falha ao conectar ao banco de dados.';
  header('Location: funcionarios_inativos.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: funcionarios_inativos.php');
  exit;
}

// Capturar dados do formulário
$id           = intval($_POST['id'] ?? 0);
$lojaOriginal = intval($_POST['loja_original'] ?? 0);
$codigo       = trim($_POST['codigo'] ?? '');
$cc           = trim($_POST['cc'] ?? '');
$nome         = trim($_POST['nome'] ?? 'Funcionário');
$endereco     = trim($_POST['endereco'] ?? '');
$cpf          = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$cargo_id     = intval($_POST['cargo_id'] ?? 0);   // agora igual ao cadastro
$loja_id      = intval($_POST['loja_id'] ?? 0);    // agora igual ao cadastro
$email        = trim($_POST['email'] ?? '');
$contratacao  = $_POST['contratacao'] ?? '';
$nascimento   = $_POST['aniversario'] ?? null;
$telefone     = trim($_POST['telefone'] ?? '');

// Validações
$erros = [];
if ($id <= 0 || $lojaOriginal <= 0) $erros[] = '❌ Funcionário inválido.';
if ($codigo === '') $erros[] = '❌ Código Manual é obrigatório.';
if ($cc === '') $erros[] = '❌ Código CC é obrigatório.';
if ($nome === '') $erros[] = '❌ Nome é obrigatório.';
if ($cpf === '') $erros[] = '❌ CPF é obrigatório.';
if ($cargo_id <= 0) $erros[] = '❌ Cargo é obrigatório.';
if ($loja_id <= 0) $erros[] = '❌ Loja é obrigatória.';
if ($contratacao === '') $erros[] = '❌ Data de contratação é obrigatória.';

function existeDuplicado($conn, $campo, $valor, $idAtual) {
  if ($valor === '') return false;
  $sql = "SELECT id FROM funcionarios WHERE $campo = ? AND id <> ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("si", $valor, $idAtual);
  $stmt->execute();
  $stmt->store_result();
  $duplicado = $stmt->num_rows > 0;
  $stmt->close();
  return $duplicado;
}

if (existeDuplicado($conn, 'cpf', $cpf, $id)) {
  $erros[] = '❌ Já existe um funcionário com este CPF.';
}
if (existeDuplicado($conn, 'codigo', $codigo, $id)) {
  $erros[] = '❌ Já existe um funcionário com este Código Manual.';
}
if (existeDuplicado($conn, 'cc', $cc, $id)) {
  $erros[] = '❌ Já existe um funcionário com este código CC.';
}

if (!empty($erros)) {
  $_SESSION['alerta'] = implode('<br>', $erros);
  header('Location: funcionarios_inativos.php');
  exit;
}

// Atualização no banco
$sql = "
  UPDATE funcionarios SET
    codigo = ?, cc = ?, nome = ?, endereco = ?, cpf = ?,
    cargo_id = ?, loja_id = ?, email = ?, contratacao = ?,
    nascimento = ?, telefone = ?, desligamento = NULL
  WHERE id = ? AND loja_id = ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  $_SESSION['alerta'] = '❌ Erro ao preparar atualização: ' . $conn->error;
  header('Location: funcionarios_inativos.php');
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
  $_SESSION['alerta'] = '✅ Funcionário "' . $nome . '" foi reativado com sucesso.';
  header('Location: funcionarios.php'); // agora vai para ativos
  exit;
} else {
  $_SESSION['alerta'] = '❌ Erro ao reativar funcionário: ' . $stmt->error;
  header('Location: funcionarios_inativos.php'); // erro continua em inativos
  exit;
}

?>
