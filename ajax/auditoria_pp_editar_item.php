<?php
require_once '../dados/conexao.php';
$conn = conectar();

header("Content-Type: application/json; charset=utf-8");

$id = intval($_POST['id'] ?? 0);
$pergunta = trim($_POST['pergunta'] ?? '');

if ($id <= 0) {
    echo json_encode(['erro' => 'ID inválido']);
    exit;
}

if ($pergunta === '') {
    echo json_encode(['erro' => 'Pergunta vazia']);
    exit;
}

$sql = "UPDATE auditoria_pp_config SET pergunta = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $pergunta, $id);

if ($stmt->execute()) {
    echo json_encode(['sucesso' => true]);
} else {
    echo json_encode(['erro' => 'Erro ao atualizar']);
}

exit;
