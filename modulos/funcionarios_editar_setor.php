<?php
require_once '../dados/conexao.php';
$conn = conectar();

$id   = intval($_POST['id'] ?? 0);
$nome = trim($_POST['nome'] ?? '');

if ($id <= 0) {
    echo json_encode(["erro" => "ID inválido."]);
    exit;
}

if ($nome === '') {
    echo json_encode(["erro" => "O nome do setor é obrigatório."]);
    exit;
}

// Verificar duplicidade
$sql = "SELECT id FROM setores WHERE nome = ? AND id <> ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $nome, $id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["erro" => "Já existe um setor com este nome."]);
    exit;
}

$sql = "UPDATE setores SET nome = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $nome, $id);

if ($stmt->execute()) {
    echo json_encode([
        "sucesso"  => true,
        "mensagem" => "Setor <strong>$nome</strong> atualizado com sucesso!"
    ]);
} else {
    echo json_encode(["erro" => "Erro ao atualizar setor."]);
}
