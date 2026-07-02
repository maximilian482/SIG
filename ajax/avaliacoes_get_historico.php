<?php
require_once '../dados/conexao.php';
$conn = conectar();

$loja = intval($_GET['loja']);
$setor = intval($_GET['setor']);

$where = "WHERE al.loja_id = $loja";

if ($setor > 0) {
    $where .= " AND a.setor_id = $setor";
}

$sql = "
SELECT 
    al.id AS avaliacao_id,
    l.nome AS loja,
    s.nome AS setor,
    al.nota_geral,
    al.data_avaliacao,
    a.nota_setor
FROM avaliacoes_loja al
JOIN lojas l ON l.id = al.loja_id
JOIN avaliacoes_setores a ON a.avaliacao_id = al.id
JOIN setores s ON s.id = a.setor_id
$where
ORDER BY al.data_avaliacao DESC
LIMIT 100
";

$res = $conn->query($sql);

$dados = [];

while ($r = $res->fetch_assoc()) {

    $class = "bom";
    if ($r['nota_geral'] < 40) $class = "ruim";
    else if ($r['nota_geral'] < 70) $class = "parcial";

    $dados[] = [
        "id" => $r['avaliacao_id'],
        "loja" => $r['loja'],
        "setor" => $r['setor'],
        "nota" => $r['nota_geral'],
        "nota_setor" => $r['nota_setor'],
        "classificacao" => $class,
        "data" => date("d/m/Y", strtotime($r['data_avaliacao']))
    ];
}

echo json_encode($dados);
