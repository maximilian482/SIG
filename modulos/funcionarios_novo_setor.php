<?php
require_once '../dados/conexao.php';
$conn = conectar();

$nome = trim($_POST['nome'] ?? '');

if ($nome === '') {
    echo json_encode(["erro" => "O nome do setor é obrigatório."]);
    exit;
}

// Verificar duplicidade
$sql = "SELECT id FROM setores WHERE nome = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $nome);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["erro" => "Já existe um setor com este nome."]);
    exit;
}

$sql = "INSERT INTO setores (nome) VALUES (?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $nome);

if ($stmt->execute()) {
    echo json_encode([
        "sucesso"  => true,
        "id"       => $stmt->insert_id,
        "nome"     => $nome,
        "mensagem" => "Setor <strong>$nome</strong> criado com sucesso!"
    ]);
} else {
    echo json_encode(["erro" => "Erro ao criar setor."]);
}
