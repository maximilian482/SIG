<?php
header("Content-Type: application/json; charset=utf-8");
require_once '../dados/conexao.php';

$conn = conectar();

$loja_id = intval($_GET['loja_id'] ?? 0);

if ($loja_id <= 0) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT 
        c.id,
        c.descricao AS pergunta,
        IF(a.item_id IS NULL, 0, 1) AS ativo
    FROM auditoria_checklist_criterios c
    LEFT JOIN auditoria_checklist_config_ativos a 
        ON a.item_id = c.id AND a.loja_id = $loja_id
    ORDER BY c.id ASC
";

$res = $conn->query($sql);

$lista = [];

while ($row = $res->fetch_assoc()) {
    $lista[] = [
        "id" => intval($row["id"]),
        "pergunta" => $row["pergunta"],
        "ativo" => intval($row["ativo"])
    ];
}

echo json_encode($lista);
