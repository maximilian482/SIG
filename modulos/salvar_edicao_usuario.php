<?php
session_start();
if ($_SESSION['perfil'] !== 'admin') {
  header('Location: ../index.php');
  exit;
}

$cpf     = $_POST['cpf'] ?? '';
$usuario = trim($_POST['usuario'] ?? '');
$senha   = trim($_POST['senha'] ?? '');
$perfil  = $_POST['perfil'] ?? 'padrao';

$vinculoArquivo = '../dados/vinculos_usuarios.json';
$usuariosArquivo = '../dados/usuarios.json';

$vinculos = file_exists($vinculoArquivo) ? json_decode(file_get_contents($vinculoArquivo), true) : [];
$usuarios = file_exists($usuariosArquivo) ? json_decode(file_get_contents($usuariosArquivo), true) : [];

if (!isset($vinculos[$cpf])) {
  echo "Vínculo não encontrado.";
  exit;
}

$antigoUsuario = $vinculos[$cpf]['usuario'];

// Atualiza vínculo
$vinculos[$cpf]['usuario'] = $usuario;
$vinculos[$cpf]['perfil']  = $perfil;

// Atualiza login
if ($usuario !== $antigoUsuario) {
  unset($usuarios[$antigoUsuario]);
}
$usuarios[$usuario] = [
  'senha'  => $senha ?: ($usuarios[$antigoUsuario]['senha'] ?? ''),
  'cargo'  => $vinculos[$cpf]['cargo'],
  'loja'   => $vinculos[$cpf]['loja'],
  'perfil' => $perfil
];

// Salva
file_put_contents($vinculoArquivo, json_encode($vinculos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents($usuariosArquivo, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header('Location: listar_usuarios.php?sucesso=Usuário atualizado com sucesso');
exit;
