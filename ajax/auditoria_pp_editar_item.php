<?php
require_once '../dados/conexao.php';
$conn = conectar();

$id = $_POST['id'] ?? null;
$pergunta = $_POST['pergunta'] ?? '';

if (!$id || !$pergunta) {
    echo "Erro";
    exit;
}

$sql = "UPDATE auditoria_pp_config SET pergunta = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $pergunta, $id);
$stmt->execute();

echo "OK";
