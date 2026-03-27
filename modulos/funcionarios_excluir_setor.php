<?php
require_once '../dados/conexao.php';
$conn = conectar();

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["erro" => "Setor inválido."]);
    exit;
}

// Verificar se está em uso
$sql = "SELECT id FROM funcionarios WHERE id_setor = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["erro" => "Este setor está vinculado a funcionários e não pode ser excluído."]);
    exit;
}

$sql = "DELETE FROM setores WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode([
        "sucesso"  => true,
        "mensagem" => "Setor excluído com sucesso!"
    ]);
} else {
    echo json_encode(["erro" => "Erro ao excluir setor."]);
}
