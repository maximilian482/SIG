<?php
header("Content-Type: application/json; charset=utf-8");
require_once '../dados/conexao.php';

$conn = conectar();

$pergunta = trim($_POST['pergunta'] ?? '');

if (empty($pergunta)) {
    echo json_encode(["erro" => "Pergunta não pode ser vazia."]);
    exit;
}

$pergunta = $conn->real_escape_string($pergunta);

$sql = "
    INSERT INTO auditoria_checklist_criterios (descricao, ativo)
    VALUES ('$pergunta', 1)
";

if (!$conn->query($sql)) {
    echo json_encode(["erro" => "Erro ao criar item."]);
    exit;
}

echo json_encode([
    "id" => $conn->insert_id,
    "pergunta" => $pergunta
]);
