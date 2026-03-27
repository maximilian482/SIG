<?php
session_start();

require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// Verifica login
if (!isset($_SESSION['cpf'])) {
    header("Location: /login.php");
    exit;
}

// Verifica permissão (opcional, caso só gerente possa alterar)
$cpf = $_SESSION['cpf'];
if (!temAcesso($conn, $cpf, 'editar_meta_loja')) {
    setFlash("erro", "❌ Você não tem permissão para alterar a meta.");
    header("Location: lojas.php");
    exit;
}

// Recebe dados
$loja_id = intval($_POST['loja_id'] ?? 0);
$meta    = floatval($_POST['meta'] ?? 0);

if ($loja_id <= 0 || $meta <= 0) {
    setFlash("erro", "❌ Dados inválidos.");
    header("Location: lojas.php");
    exit;
}

// Atualiza meta
$stmt = $conn->prepare("
    UPDATE lojas 
    SET meta = ? 
    WHERE id = ?
");

$stmt->bind_param("di", $meta, $loja_id);
$stmt->execute();

setFlash("success", "✔️ Meta atualizada com sucesso!");
header("Location: lojas.php");
exit;
