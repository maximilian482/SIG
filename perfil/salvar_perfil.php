<?php
session_start();

require_once __DIR__ . '/../dados/conexao.php';
$conn = conectar();

// Verifica se a sessão está ativa
if (!isset($_SESSION['id_funcionario']) || !isset($_SESSION['usuario'])) {
  $_SESSION['erro_foto'] = 'Sessão expirada. Faça login novamente.';
  header('Location: perfil.php');
  exit;
}

$id = $_SESSION['id_funcionario'];

// Valida campos obrigatórios
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$sobre_mim = trim($_POST['sobre_mim'] ?? '');

if (!$email || !$telefone || !$endereco) {
  $_SESSION['erro_foto'] = 'Preencha todos os campos obrigatórios.';
  header('Location: perfil.php');
  exit;
}

// Atualiza dados no banco
$stmt = $conn->prepare("UPDATE funcionarios SET email=?, telefone=?, endereco=?, sobre_mim=? WHERE id=?");
$stmt->bind_param("ssssi", $email, $telefone, $endereco, $sobre_mim, $id);

if ($stmt->execute()) {
  $_SESSION['sucesso_foto'] = 'Perfil atualizado com sucesso!';
} else {
  $_SESSION['erro_foto'] = 'Erro ao atualizar perfil. Tente novamente.';
}

header('Location: perfil.php');
exit;
