<?php
require_once '../dados/conexao.php';
$conn = conectar();

$id = $_POST['id'] ?? null;

if (!$id) {
    echo "Erro";
    exit;
}

// Excluir item
$sql = "DELETE FROM auditoria_pp_config WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

// Excluir ativações
$sql2 = "DELETE FROM auditoria_pp_config_ativos WHERE item_id = ?";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $id);
$stmt2->execute();

echo "OK";
