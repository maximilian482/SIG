<?php
require_once '../dados/conexao.php';
$conn = conectar();

$id = intval($_GET['id']);

$sql = "
SELECT 
    al.*, 
    l.nome AS loja
FROM avaliacoes_loja al
JOIN lojas l ON l.id = al.loja_id
WHERE al.id = $id
";

$res = $conn->query($sql);
$avaliacao = $res->fetch_assoc();

// Buscar setores
$sql2 = "
SELECT 
    a.id AS setor_avaliado_id,
    sp.nome_setor AS setor,
    a.nota_setor,
    a.observacao
FROM avaliacoes_setores a
JOIN setores_padrao sp ON sp.id = a.setor_id
WHERE a.avaliacao_id = $id
";



$res2 = $conn->query($sql2);

$setores = [];

while ($s = $res2->fetch_assoc()) {

    // Buscar critérios
    $sql3 = "
    SELECT criterio, valor
    FROM avaliacoes_setores_criterios
    WHERE avaliacao_setor_id = {$s['setor_avaliado_id']}
    ";

    $res3 = $conn->query($sql3);

    $criterios = [];

    while ($c = $res3->fetch_assoc()) {
        $criterios[] = $c;
    }

    $s['criterios'] = $criterios;
    $setores[] = $s;
}

// Calcular gráfico geral
$sql4 = "
SELECT 
    SUM(CASE WHEN valor = 0 THEN 1 ELSE 0 END) AS ruim,
    SUM(CASE WHEN valor = 50 THEN 1 ELSE 0 END) AS parcial,
    SUM(CASE WHEN valor = 100 THEN 1 ELSE 0 END) AS bom
FROM avaliacoes_setores_criterios c
JOIN avaliacoes_setores s ON s.id = c.avaliacao_setor_id
WHERE s.avaliacao_id = $id
";

$res4 = $conn->query($sql4);
$grafico = $res4->fetch_assoc();

echo json_encode([
    "avaliacao" => array_merge($avaliacao, [
        "qtd_ruim" => $grafico['ruim'],
        "qtd_parcial" => $grafico['parcial'],
        "qtd_bom" => $grafico['bom']
    ]),
    "setores" => $setores
]);
