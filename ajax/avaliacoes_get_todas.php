<?php
require_once '../dados/conexao.php';
$conn = conectar();

$sql = "
SELECT 
    al.id,
    al.loja_id,
    l.nome AS loja,
    al.nota_geral,
    al.data_avaliacao
FROM avaliacoes_loja al
JOIN lojas l ON l.id = al.loja_id
ORDER BY al.data_avaliacao DESC
";

$res = $conn->query($sql);

$dados = [];

while ($r = $res->fetch_assoc()) {

    $class = "bom";
    if ($r['nota_geral'] < 40) $class = "ruim";
    else if ($r['nota_geral'] < 70) $class = "parcial";

    $dados[] = [
        "id" => $r['id'],
        "loja_id" => $r['loja_id'],   // ESSENCIAL PARA O FILTRO
        "loja" => $r['loja'],
        "nota" => $r['nota_geral'],
        "classificacao" => $class,
        "data" => date("d/m/Y", strtotime($r['data_avaliacao']))
    ];
}

echo json_encode($dados);
