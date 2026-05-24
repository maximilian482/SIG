<?php
session_start();
require_once '../dados/conexao.php';
require_once '../includes/funcoes.php';

$conn = conectar();

if (!isset($_SESSION['cpf'])) {
    echo json_encode(["sucesso" => false, "mensagem" => "Não autenticado"]);
    exit;
}

$cpf = $_SESSION['cpf'];

// Verifica permissão
if (!temAcesso($conn, $cpf, 'avaliacoes_loja')) {
    echo json_encode(["sucesso" => false, "mensagem" => "Sem permissão"]);
    exit;
}

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["sucesso" => false, "mensagem" => "ID inválido"]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM avaliacoes_loja WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["sucesso" => true]);
} else {
    echo json_encode(["sucesso" => false, "mensagem" => "Erro ao excluir"]);
}
