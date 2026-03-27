<?php
session_start();

require_once __DIR__ . '/../dados/conexao.php';
$conn = conectar();

// Verifica sessão
if (!isset($_SESSION['id_funcionario']) || !isset($_SESSION['usuario'])) {
    $_SESSION['erro_foto'] = 'Sessão expirada. Faça login novamente.';
    header('Location: perfil.php');
    exit;
}

$id = $_SESSION['id_funcionario'];

// Campos obrigatórios
$email     = trim($_POST['email'] ?? '');
$telefone  = trim($_POST['telefone'] ?? '');
$endereco  = trim($_POST['endereco'] ?? '');
$sobre_mim = trim($_POST['sobre_mim'] ?? '');

if (!$email || !$telefone || !$endereco) {
    $_SESSION['erro_foto'] = 'Preencha todos os campos obrigatórios.';
    header('Location: perfil.php');
    exit;
}

// ===============================
// 1. PROCESSAR FOTO (SE ENVIADA)
// ===============================
$fotoNova = null;

if (!empty($_FILES['foto']['name'])) {

    $dir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/perfil/";

    // Cria a pasta se não existir
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    // Extensão segura
    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $permitidas)) {
        $_SESSION['erro_foto'] = 'Formato de imagem inválido. Envie JPG, PNG, GIF ou WEBP.';
        header('Location: perfil.php');
        exit;
    }

    // Nome único
    $nomeArquivo = "perfil_" . $id . "_" . time() . "." . $ext;
    $destino = $dir . $nomeArquivo;

    if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
        $fotoNova = $nomeArquivo;

        // Buscar foto antiga
        $stmt = $conn->prepare("SELECT foto FROM funcionarios WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($res && !empty($res['foto'])) {
            // Caminhos possíveis (compatibilidade)
            $antigo1 = $_SERVER['DOCUMENT_ROOT'] . "/uploads/perfil/" . $res['foto'];
            $antigo2 = $_SERVER['DOCUMENT_ROOT'] . "/uploads/" . $res['foto'];

            if (file_exists($antigo1)) unlink($antigo1);
            if (file_exists($antigo2)) unlink($antigo2);
        }
    }
}

// ===============================
// 2. ATUALIZAR DADOS
// ===============================

if ($fotoNova) {
    $stmt = $conn->prepare("
        UPDATE funcionarios 
        SET email=?, telefone=?, endereco=?, sobre_mim=?, foto=? 
        WHERE id=?
    ");
    $stmt->bind_param("sssssi", $email, $telefone, $endereco, $sobre_mim, $fotoNova, $id);
} else {
    $stmt = $conn->prepare("
        UPDATE funcionarios 
        SET email=?, telefone=?, endereco=?, sobre_mim=? 
        WHERE id=?
    ");
    $stmt->bind_param("ssssi", $email, $telefone, $endereco, $sobre_mim, $id);
}

if ($stmt->execute()) {
    $_SESSION['sucesso_foto'] = 'Perfil atualizado com sucesso!';
} else {
    $_SESSION['erro_foto'] = 'Erro ao atualizar perfil. Tente novamente.';
}

$stmt->close();
header('Location: perfil.php');
exit;
