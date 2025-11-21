<?php
session_start();
if ($_SESSION['perfil'] !== 'admin') {
  header('Location: ../index.php');
  exit;
}

$cpf   = trim($_POST['cpf'] ?? '');
$nome  = trim($_POST['nome'] ?? '');
$cargo = trim($_POST['cargo'] ?? '');
$loja  = $_POST['loja'] ?? null;

if (!$cpf || !$nome || !$cargo) {
  header('Location: cadastrar_funcionario.php?erro=Campos obrigatórios não preenchidos');
  exit;
}

$cpf = preg_replace('/\D/', '', $cpf); // remove caracteres não numéricos

$arquivo = '../dados/funcionarios.json';
$funcionarios = file_exists($arquivo) ? json_decode(file_get_contents($arquivo), true) : [];
$funcionarios = is_array($funcionarios) ? $funcionarios : [];

if (isset($funcionarios[$cpf])) {
  header('Location: cadastrar_funcionario.php?erro=Funcionário já cadastrado');
  exit;
}

$funcionarios[$cpf] = [
  'nome'   => $nome,
  'cargo'  => $cargo,
  'loja'   => $loja ?: null,
  'status' => 'ativo'
];

file_put_contents($arquivo, json_encode($funcionarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header('Location: ../index.php?sucesso=Funcionário cadastrado com sucesso');
exit;
