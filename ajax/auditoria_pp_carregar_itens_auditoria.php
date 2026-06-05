<?php
require_once '../dados/conexao.php';
$conn = conectar();

$lojaId = intval($_GET['loja_id'] ?? 0);

if ($lojaId <= 0) {
    echo json_encode([]);
    exit;
}

// Buscar itens ativos da loja
$sql = "
    SELECT c.id, c.pergunta
    FROM auditoria_pp_config c
    INNER JOIN auditoria_pp_config_ativos a ON a.item_id = c.id
    WHERE a.loja_id = ?
    ORDER BY c.pergunta
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$res = $stmt->get_result();

$itens = [];
while ($row = $res->fetch_assoc()) {
    $itens[] = [
        "id" => intval($row["id"]),
        "pergunta" => $row["pergunta"]
    ];
}

header("Content-Type: application/json");
echo json_encode($itens);
