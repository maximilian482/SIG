<?php
header("Content-Type: application/json; charset=utf-8");
require_once '../dados/conexao.php';

$conn = conectar();

$sql = "
    SELECT 
        a.id,
        l.nome AS loja,
        a.nota_geral,
        a.data_auditoria
    FROM auditoria_checklist a
    JOIN lojas l ON l.id = a.loja_id
    ORDER BY a.id DESC
    LIMIT 10
";

$res = $conn->query($sql);

$lista = [];

while ($row = $res->fetch_assoc()) {

    $nota = floatval($row["nota_geral"]);

    // Nota já está no formato 0–100
    $lista[] = [
        "id" => intval($row["id"]),
        "loja" => $row["loja"],
        "nota" => number_format($nota, 2, ',', '.'),
        "data" => date("d/m/Y", strtotime($row["data_auditoria"]))
    ];
}

echo json_encode($lista);
