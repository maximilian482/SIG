<?php
session_start();
require_once '../dados/conexao.php';

$conn = conectar();
if (!$conn) {
  $_SESSION['erros_funcionario'] = ['❌ Falha ao conectar ao banco de dados.'];
  $_SESSION['dados_funcionario'] = $_POST ?? [];
  header('Location: funcionarios_adicionar.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: funcionarios_listar.php');
  exit;
}

// Capturar dados do formulário
$codigo       = trim($_POST['codigo'] ?? '0');
$cc           = trim($_POST['cc'] ?? '0');
$nome         = trim($_POST['nome'] ?? '');
$endereco     = trim($_POST['endereco'] ?? '');
$cpf          = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$cargo_id     = intval($_POST['cargo_id'] ?? 0);
$loja_id      = intval($_POST['loja_id'] ?? 0);
$id_setor     = intval($_POST['id_setor'] ?? 0);
$email        = trim($_POST['email'] ?? '');
$contratacao  = $_POST['contratacao'] ?? '';
$nascimento   = $_POST['aniversario'] ?? null;
$telefone     = trim($_POST['telefone'] ?? '');
$eh_funcionario = intval($_POST['eh_funcionario'] ?? 1);

// Loja GERAL se não vier nada
if ($loja_id <= 0) {
    $resGeral = $conn->query("SELECT id FROM lojas WHERE nome = 'GERAL' LIMIT 1");
    if ($resGeral && $resGeral->num_rows > 0) {
        $loja_id = $resGeral->fetch_assoc()['id'];
    }
}

// Validações
$erros = [];

if ($nome === '') {
    $erros[] = '❌ O nome é obrigatório.';
}

if ($id_setor <= 0) {
    $erros[] = '❌ O setor é obrigatório.';
}

if (strlen($cpf) !== 11) {
    $erros[] = '❌ CPF inválido.';
}

function existeDuplicado($conn, $campo, $valor) {
  if ($valor === '' || $valor === '0') return false;
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

// CPF sempre deve ser único
if (existeDuplicado($conn, 'cpf', $cpf)) {
  $erros[] = '❌ Já existe um funcionário com este CPF.';
}

// Código manual só valida se for diferente de 0
if ($codigo !== '0' && existeDuplicado($conn, 'codigo', $codigo)) {
  $erros[] = '❌ Já existe um funcionário com este Código Manual.';
}

// CC só valida se for diferente de 0
if ($cc !== '0' && existeDuplicado($conn, 'cc', $cc)) {
  $erros[] = '❌ Já existe um funcionário com este código CC.';
}

if (!empty($erros)) {
  $_SESSION['erros_funcionario'] = $erros;
  $_SESSION['dados_funcionario'] = $_POST;
  header('Location: funcionarios_adicionar.php');
  exit;
}

// Gerar senha padrão
$senhaPadrao = substr($cpf, 0, 6);
if (strlen($senhaPadrao) < 6) {
  $_SESSION['erros_funcionario'] = ['❌ CPF inválido para gerar senha padrão.'];
  $_SESSION['dados_funcionario'] = $_POST;
  header('Location: funcionarios_adicionar.php');
  exit;
}
$hash = password_hash($senhaPadrao, PASSWORD_DEFAULT);

// Inserção no banco
$sql = "
  INSERT INTO funcionarios (
    codigo, cc, nome, endereco, cpf, cargo_id, loja_id, id_setor,
    email, contratacao, nascimento, telefone, senha, eh_funcionario, desligamento
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  $_SESSION['erros_funcionario'] = ['❌ Erro ao preparar inserção: ' . $conn->error];
  $_SESSION['dados_funcionario'] = $_POST;
  header('Location: funcionarios_adicionar.php');
  exit;
}

$stmt->bind_param(
  'sssssiissssssi',
  $codigo, $cc, $nome, $endereco, $cpf, $cargo_id, $loja_id, $id_setor,
  $email, $contratacao, $nascimento, $telefone, $hash, $eh_funcionario
);

if ($stmt->execute()) {

  // Buscar nome da loja para mensagem
  $nomeLoja = "GERAL";
  $res = $conn->query("SELECT nome FROM lojas WHERE id = $loja_id");
  if ($res && $res->num_rows > 0) {
    $nomeLoja = $res->fetch_assoc()['nome'];
  }

  $_SESSION['sucesso'] = "Funcionário <strong>$nome</strong> foi adicionado com sucesso na loja <strong>$nomeLoja</strong>.";

  $stmt->close();
  unset($_SESSION['erros_funcionario'], $_SESSION['dados_funcionario']);

   header('Location: funcionarios.php');
  exit;

} else {
  $erro = $stmt->error;
  $stmt->close();
  $_SESSION['erros_funcionario'] = ['❌ Erro ao salvar funcionário: ' . $erro];
  $_SESSION['dados_funcionario'] = $_POST;
  header('Location: funcionarios_adicionar.php');
  exit;
}
