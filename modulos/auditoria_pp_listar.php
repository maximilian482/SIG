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
    FROM auditoria_pp a
    JOIN lojas l ON l.id = a.loja_id
    ORDER BY a.id DESC
    LIMIT 10
";

$res = $conn->query($sql);

$lista = [];

while ($row = $res->fetch_assoc()) {

    // ================================
    // CONVERTER NOTA 0/5/10 → 0/50/100
    // ================================
    $nota = floatval($row["nota_geral"]);

    if ($nota == 10) {
        $nota = 100;
    } elseif ($nota == 5) {
        $nota = 50;
    } else {
        $nota = 0;
    }

    // ================================
    // MONTAR LINHA
    // ================================
    $lista[] = [
        "id" => intval($row["id"]),
        "loja" => $row["loja"],
        "nota_geral" => number_format($nota, 2, ',', '.'),
        "data" => date("d/m/Y", strtotime($row["data_auditoria"]))
    ];
}

echo json_encode($lista);
