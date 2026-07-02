<?php
header("Content-Type: application/json; charset=utf-8");
require_once '../dados/conexao.php';

$conn = conectar();

$pagina = intval($_GET['pagina'] ?? 1);
$loja   = intval($_GET['loja'] ?? 0);
$data_ini = $_GET['data_ini'] ?? "";
$data_fim = $_GET['data_fim'] ?? "";

$limite = 20;
$offset = ($pagina - 1) * $limite;

$where = " WHERE 1=1 ";

if ($loja > 0) {
    $where .= " AND a.loja_id = $loja ";
}

if (!empty($data_ini)) {
    $where .= " AND a.data_auditoria >= '$data_ini' ";
}

if (!empty($data_fim)) {
    $where .= " AND a.data_auditoria <= '$data_fim' ";
}

$sql = "
    SELECT 
        a.id,
        l.nome AS loja,
        a.nota_geral,
        a.data_auditoria
    FROM auditoria_checklist a
    JOIN lojas l ON l.id = a.loja_id
    $where
    ORDER BY a.id DESC
    LIMIT $offset, $limite
";

$res = $conn->query($sql);

$lista = [];

while ($row = $res->fetch_assoc()) {

    $nota = floatval($row["nota_geral"]);

    $classe = "nota-ruim";
    if ($nota >= 90) $classe = "nota-otimo";
    elseif ($nota >= 70) $classe = "nota-bom";
    elseif ($nota >= 50) $classe = "nota-regular";

    $lista[] = [
        "id" => intval($row["id"]),
        "loja" => $row["loja"],
        "nota" => number_format($nota, 2, ',', '.'),
        "classeNota" => $classe,
        "data" => date("d/m/Y", strtotime($row["data_auditoria"]))
    ];
}

echo json_encode($lista);
