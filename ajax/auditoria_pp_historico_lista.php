<?php
require_once '../dados/conexao.php';
$conn = conectar();

header('Content-Type: application/json; charset=utf-8');

// PAGINAÇÃO
$pagina = intval($_GET['pagina'] ?? 1);
$limite = 10;
$offset = ($pagina - 1) * $limite;

// FILTROS
$filtro_loja = $_GET['loja'] ?? '';
$filtro_ini  = $_GET['data_ini'] ?? '';
$filtro_fim  = $_GET['data_fim'] ?? '';

$where = [];

// FILTRO LOJA
if ($filtro_loja !== '') {
    $where[] = "a.loja_id = " . intval($filtro_loja);
}

// FILTRO DATA INICIAL
if ($filtro_ini !== '') {
    $where[] = "a.data_auditoria >= '" . $conn->real_escape_string($filtro_ini) . "'";
}

// FILTRO DATA FINAL
if ($filtro_fim !== '') {
    $where[] = "a.data_auditoria <= '" . $conn->real_escape_string($filtro_fim) . "'";
}

$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// BUSCA
$sql = "
    SELECT 
        a.id,
        l.nome AS loja,
        a.nota_geral,
        DATE_FORMAT(a.data_auditoria, '%d/%m/%Y') AS data
    FROM auditoria_pp a
    JOIN lojas l ON l.id = a.loja_id
    $whereSQL
    ORDER BY a.data_auditoria DESC
    LIMIT $limite OFFSET $offset
";

$res = $conn->query($sql);

$lista = [];

while ($row = $res->fetch_assoc()) {

    // CLASSE DE COR DA NOTA
    $classe = "nota-media";
    if ($row['nota_geral'] >= 90) $classe = "nota-alta";
    if ($row['nota_geral'] < 70)  $classe = "nota-baixa";

    $lista[] = [
        'id' => $row['id'],
        'loja' => $row['loja'],
        'nota' => $row['nota_geral'],
        'classeNota' => $classe,
        'data' => $row['data']
    ];
}

echo json_encode($lista);
exit;
