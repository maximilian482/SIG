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
        c.descricao AS pergunta
    FROM auditoria_checklist_criterios c
    JOIN auditoria_checklist_config_ativos a 
        ON a.item_id = c.id
    WHERE a.loja_id = ?
    ORDER BY c.id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $loja_id);
$stmt->execute();
$res = $stmt->get_result();

$lista = [];

while ($row = $res->fetch_assoc()) {
    $lista[] = [
        "id" => intval($row["id"]),
        "pergunta" => $row["pergunta"]
    ];
}

echo json_encode($lista);
