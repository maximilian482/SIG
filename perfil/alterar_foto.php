<?php
session_start();

require_once __DIR__ . '/../dados/conexao.php';
$conn = conectar();

// Verifica se a sessão está ativa
if (!isset($_SESSION['id_funcionario']) || !isset($_SESSION['usuario'])) {
  $_SESSION['erro_foto'] = 'Sessão expirada ou envio inválido. Tente novamente.';
  header('Location: perfil.php');
  exit;
}

$id = $_SESSION['id_funcionario'];

// Verifica se o arquivo foi enviado corretamente
if (!isset($_FILES['nova_foto']) || $_FILES['nova_foto']['error'] !== UPLOAD_ERR_OK) {
  $_SESSION['erro_foto'] = 'Erro ao enviar a imagem. Tente novamente.';
  header('Location: perfil.php');
  exit;
}

$foto = $_FILES['nova_foto'];
$maxTamanho = 5 * 1024 * 1024; // 5MB

// Valida tamanho
if ($foto['size'] > $maxTamanho) {
  $_SESSION['erro_foto'] = 'Imagem muito grande. Escolha uma da galeria com até 5MB.';
  header('Location: perfil.php');
  exit;
}

// Valida extensão
$ext = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
$permitidas = ['jpg','jpeg','png','gif'];
if (!in_array($ext, $permitidas)) {
  $_SESSION['erro_foto'] = 'Formato de imagem não permitido. Use JPG, PNG ou GIF.';
  header('Location: perfil.php');
  exit;
}

// Define nome e caminho
$fotoNome = "perfil_" . time() . "." . $ext;
$destino = __DIR__ . "/../uploads/" . $fotoNome;

// Move e atualiza no banco
if (move_uploaded_file($foto['tmp_name'], $destino)) {
  $stmt = $conn->prepare("UPDATE funcionarios SET foto=? WHERE id=?");
  $stmt->bind_param("si", $fotoNome, $id);
  $stmt->execute();
  $_SESSION['sucesso_foto'] = 'Foto alterada com sucesso!';
} else {
  $_SESSION['erro_foto'] = 'Falha ao salvar a imagem. Tente novamente.';
}

header("Location: perfil.php");
exit;
