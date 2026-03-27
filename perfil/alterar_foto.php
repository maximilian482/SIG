<?php
session_start();

require_once __DIR__ . '/../dados/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$conn = conectar();

// Verifica sessão
if (!isset($_SESSION['id_funcionario']) || !isset($_SESSION['usuario'])) {
    $_SESSION['erro_foto'] = 'Sessão expirada ou envio inválido. Faça login novamente.';
    header('Location: perfil.php');
    exit;
}

$id = $_SESSION['id_funcionario'];

// Verifica envio
if (!isset($_FILES['nova_foto']) || $_FILES['nova_foto']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['erro_foto'] = 'Erro ao enviar a imagem. Tente novamente.';
    header('Location: perfil.php');
    exit;
}

$foto = $_FILES['nova_foto'];
$maxTamanho = 5 * 1024 * 1024; // 5MB

// Valida tamanho
if ($foto['size'] > $maxTamanho) {
    $_SESSION['erro_foto'] = 'Imagem muito grande. Escolha uma com até 5MB.';
    header('Location: perfil.php');
    exit;
}

// Valida extensão
$ext = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
$permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($ext, $permitidas)) {
    $_SESSION['erro_foto'] = 'Formato inválido. Use JPG, PNG, GIF ou WEBP.';
    header('Location: perfil.php');
    exit;
}

// Diretório novo
$dir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/perfil/";

// Cria pasta se não existir
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

// Nome único
$fotoNome = "perfil_" . $id . "_" . time() . "." . $ext;
$destino = $dir . $fotoNome;

// Move arquivo
if (move_uploaded_file($foto['tmp_name'], $destino)) {

    // Buscar foto antiga
    $stmt = $conn->prepare("SELECT foto FROM funcionarios WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res && !empty($res['foto'])) {
        $antiga1 = $_SERVER['DOCUMENT_ROOT'] . "/uploads/perfil/" . $res['foto'];
        $antiga2 = $_SERVER['DOCUMENT_ROOT'] . "/uploads/" . $res['foto']; // compatibilidade

        if (file_exists($antiga1)) unlink($antiga1);
        if (file_exists($antiga2)) unlink($antiga2);
    }

    // Atualiza banco
    $stmt = $conn->prepare("UPDATE funcionarios SET foto=? WHERE id=?");
    $stmt->bind_param("si", $fotoNome, $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['sucesso_foto'] = 'Foto alterada com sucesso!';
} else {
    $_SESSION['erro_foto'] = 'Falha ao salvar a imagem. Tente novamente.';
}

header("Location: perfil.php");
exit;
