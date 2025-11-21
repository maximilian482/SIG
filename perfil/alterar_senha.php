<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar(); 


if (!isset($_SESSION['usuario'])) {
  header('Location: ../login.php');
  exit;
}

$id = $_SESSION['id_funcionario'];
$senhaAtual = $_POST['senha_atual'] ?? '';
$novaSenha = $_POST['nova_senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';

// Buscar senha atual no banco
$stmt = $conn->prepare("SELECT senha FROM funcionarios WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result) {
    echo "<script>alert('Usuário não encontrado.'); window.location='perfil.php';</script>";
    exit;
}

$senhaHash = $result['senha'];

// Validar senha atual
if (!password_verify($senhaAtual, $senhaHash)) {
    echo "<script>alert('❌ Senha atual incorreta.'); window.location='perfil.php';</script>";
    exit;
}

// Validar nova senha
if ($novaSenha !== $confirmarSenha) {
    echo "<script>alert('❌ As senhas não coincidem.'); window.location='perfil.php';</script>";
    exit;
}

if (strlen($novaSenha) < 6) {
    echo "<script>alert('❌ A nova senha deve ter pelo menos 6 caracteres.'); window.location='perfil.php';</script>";
    exit;
}

// Atualizar senha
$novaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
$stmtUp = $conn->prepare("UPDATE funcionarios SET senha=? WHERE id=?");
$stmtUp->bind_param("si", $novaHash, $id);
$stmtUp->execute();

echo "<script>alert('✅ Senha alterada com sucesso!'); window.location='perfil.php';</script>";
