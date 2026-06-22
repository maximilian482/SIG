<?php
require_once '../dados/conexao.php';
$conn = conectar();

header('Content-Type: application/json; charset=utf-8');

// Receber JSON
$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(['sucesso' => false, 'erro' => 'Nenhum dado recebido']);
    exit;
}

$loja_id = intval($input['loja_id']);
$ativos  = $input['ativos'] ?? [];

// ===============================
// VALIDAR
// ===============================
if (!$loja_id) {
    echo json_encode(['sucesso' => false, 'erro' => 'Loja não informada']);
    exit;
}

// ===============================
// APAGAR CONFIGURAÇÃO ANTIGA
// ===============================
$sqlDel = "DELETE FROM auditoria_pp_config_ativos WHERE loja_id = $loja_id";
$conn->query($sqlDel);

// ===============================
// INSERIR NOVA CONFIGURAÇÃO
// ===============================
foreach ($ativos as $item_id) {

    $item_id = intval($item_id);

    $sqlIns = "
        INSERT INTO auditoria_pp_config_ativos (loja_id, item_id)
        VALUES ($loja_id, $item_id)
    ";

    $conn->query($sqlIns);
}

// ===============================
// RETORNO
// ===============================
echo json_encode(['sucesso' => true]);
exit;
