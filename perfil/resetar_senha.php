<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar(); 


if (!isset($_SESSION['usuario'])) {
  header('Location: ../login.php');
  exit;
}

$id = $_SESSION['id_funcionario'];

// Buscar CPF do usuário
$stmt = $conn->prepare("SELECT cpf FROM funcionarios WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result) {
    echo "<script>alert('Usuário não encontrado.'); window.location='../modulos/gerenciar_acessos.php';</script>";
    exit;
}



$cpf = preg_replace('/\D/', '', $result['cpf']); // remove caracteres não numéricos
$senhaPadrao = substr($cpf, 0, 6);

if (strlen($senhaPadrao) < 6) {
    echo "<script>alert('❌ CPF inválido para resetar senha.'); window.location='perfil.php';</script>";
    exit;
}

$hash = password_hash($senhaPadrao, PASSWORD_DEFAULT);

// Atualizar senha
$stmtUp = $conn->prepare("UPDATE funcionarios SET senha=? WHERE id=?");
$stmtUp->bind_param("si", $hash, $id);
$stmtUp->execute();


header("Location: ../modulos/gerenciar_acessos.php?senha_resetada=1");
exit;
