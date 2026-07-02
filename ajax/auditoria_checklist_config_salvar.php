<?php
header("Content-Type: application/json; charset=utf-8");
require_once '../dados/conexao.php';

$conn = conectar();

$input = json_decode(file_get_contents("php://input"), true);

$loja_id = intval($input['loja_id'] ?? 0);
$ativos = $input['ativos'] ?? [];

if ($loja_id <= 0) {
    echo json_encode(["sucesso" => false, "erro" => "Loja inválida."]);
    exit;
}

// Remove todos os ativos da loja
$conn->query("DELETE FROM auditoria_checklist_config_ativos WHERE loja_id = $loja_id");

// Insere os novos
foreach ($ativos as $item_id) {
    $item_id = intval($item_id);
    $conn->query("
        INSERT INTO auditoria_checklist_config_ativos (loja_id, item_id)
        VALUES ($loja_id, $item_id)
    ");
}

echo json_encode(["sucesso" => true]);
