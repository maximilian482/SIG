<?php
header("Content-Type: application/json; charset=utf-8");
require_once '../dados/conexao.php';

$conn = conectar();

$id = intval($_POST['id'] ?? 0);
$pergunta = trim($_POST['pergunta'] ?? '');

if ($id <= 0 || empty($pergunta)) {
    echo json_encode(["erro" => "Dados inválidos."]);
    exit;
}

$pergunta = $conn->real_escape_string($pergunta);

$sql = "
    UPDATE auditoria_checklist_criterios
    SET descricao = '$pergunta'
    WHERE id = $id
";

if (!$conn->query($sql)) {
    echo json_encode(["erro" => "Erro ao atualizar item."]);
    exit;
}

echo json_encode(["sucesso" => true]);
