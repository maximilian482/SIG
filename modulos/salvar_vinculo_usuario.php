<?php
session_start();
if ($_SESSION['perfil'] !== 'admin') {
  header('Location: ../index.php');
  exit;
}

// Captura os dados do formulário
$cpf     = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$nome    = trim($_POST['nome'] ?? '');
$usuario = trim($_POST['usuario'] ?? '');
$senha   = trim($_POST['senha'] ?? '');
$cargo   = trim($_POST['cargo'] ?? '');
$loja    = $_POST['loja'] ?? null;
$perfil  = $_POST['perfil'] ?? 'padrao';

// Validação básica
if (!$cpf || strlen($cpf) !== 11 || !$nome || !$usuario || !$senha || !$cargo || !$perfil) {
  header('Location: cadastrar_usuario.php?erro=' . urlencode('Campos obrigatórios inválidos ou CPF incorreto'));
  exit;
}

// Verifica se o CPF existe em funcionarios.json
$funcionarios = json_decode(@file_get_contents('../dados/funcionarios.json'), true);
$funcionarios = is_array($funcionarios) ? $funcionarios : [];

$cpfValido = false;
foreach ($funcionarios as $lojaKey => $lista) {
  foreach ($lista as $f) {
    if (isset($f['cpf']) && preg_replace('/\D/', '', $f['cpf']) === $cpf) {
      $cpfValido = true;
      break 2;
    }
  }
}

if (!$cpfValido) {
  header('Location: cadastrar_usuario.php?erro=' . urlencode('CPF não encontrado na lista de funcionários'));
  exit;
}

// Verifica se o CPF já está vinculado
$vinculoArquivo = '../dados/vinculos_usuarios.json';
$vinculos = file_exists($vinculoArquivo) ? json_decode(file_get_contents($vinculoArquivo), true) : [];
$vinculos = is_array($vinculos) ? $vinculos : [];

if (isset($vinculos[$cpf])) {
  header('Location: cadastrar_usuario.php?erro=' . urlencode('Funcionário já vinculado a um usuário'));
  exit;
}

// Verifica se o nome de usuário já existe
$usuariosArquivo = '../dados/usuarios.json';
$usuarios = file_exists($usuariosArquivo) ? json_decode(file_get_contents($usuariosArquivo), true) : [];
$usuarios = is_array($usuarios) ? $usuarios : [];

if (isset($usuarios[$usuario])) {
  header('Location: cadastrar_usuario.php?erro=' . urlencode('Nome de usuário já existe'));
  exit;
}

// Salva vínculo no vinculos_usuarios.json
$vinculos[$cpf] = [
  'nome'    => $nome,
  'usuario' => $usuario,
  'cargo'   => $cargo,
  'loja'    => $loja ?: null,
  'perfil'  => $perfil
];

file_put_contents($vinculoArquivo, json_encode($vinculos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Salva login em usuarios.json
$usuarios[$usuario] = [
  'cpf'    => $cpf,
  'nome'   => $nome,
  'senha'  => $senha,
  'cargo'  => $cargo,
  'loja'   => $loja ?: null,
  'perfil' => $perfil
];

file_put_contents($usuariosArquivo, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Redireciona com sucesso
header('Location: cadastrar_usuario.php?sucesso=' . urlencode('Usuário vinculado com sucesso'));
exit;
