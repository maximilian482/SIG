<?php
require_once '../dados/conexao.php';
$conn = conectar();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['loja_id'])) {
    echo json_encode(['erro' => 'Loja não informada']);
    exit;
}

$loja_id = intval($_GET['loja_id']);

/*
---------------------------------------------------------
1) BUSCAR TODAS AS PERGUNTAS
---------------------------------------------------------
*/
$sqlPerguntas = "
    SELECT id, pergunta
    FROM auditoria_pp_config
    ORDER BY id
";

$resPerguntas = $conn->query($sqlPerguntas);

/*
---------------------------------------------------------
2) BUSCAR ITENS ATIVOS PARA A LOJA
---------------------------------------------------------
*/
$sqlAtivos = "
    SELECT item_id
    FROM auditoria_pp_config_ativos
    WHERE loja_id = $loja_id
";

$resAtivos = $conn->query($sqlAtivos);

$ativos = [];
while ($a = $resAtivos->fetch_assoc()) {
    $ativos[] = intval($a['item_id']);
}

/*
---------------------------------------------------------
3) MONTAR LISTA FINAL
---------------------------------------------------------
*/
$lista = [];

while ($p = $resPerguntas->fetch_assoc()) {

    $lista[] = [
        'id'       => intval($p['id']),
        'pergunta' => $p['pergunta'],
        'ativo'    => in_array($p['id'], $ativos)
    ];
}

echo json_encode($lista);
exit;
