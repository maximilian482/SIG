<?php
session_start();

require_once '../dados/conexao.php';
$conn = conectar();

if (!isset($_SESSION['cpf'])) {
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}

$nome = trim($_POST['nome'] ?? '');

if ($nome === '') {
    echo json_encode(['erro' => 'Nome inválido']);
    exit;
}

// Verifica duplicidade
$sql = "SELECT id FROM setores_padrao WHERE nome_setor = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $nome);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['erro' => 'Este setor já existe.']);
    exit;
}

// Insere
$sql = "INSERT INTO setores_padrao (nome_setor) VALUES (?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $nome);
$stmt->execute();

echo json_encode([
    'id' => $stmt->insert_id,
    'nome' => $nome
]);
