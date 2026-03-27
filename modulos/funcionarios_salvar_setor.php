<?php
require_once '../dados/conexao.php';
$conn = conectar();

$nome = trim($_POST['nome'] ?? '');

if ($nome === '') {
    echo json_encode(['erro' => 'Nome inválido']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO setores (nome) VALUES (?)");
$stmt->bind_param("s", $nome);

if ($stmt->execute()) {
    echo json_encode([
        'sucesso' => true,
        'id' => $stmt->insert_id,
        'nome' => $nome
    ]);
} else {
    echo json_encode(['erro' => 'Erro ao salvar setor']);
}
