<?php
require_once '../dados/conexao.php';
$conn = conectar();

header("Content-Type: application/json; charset=utf-8");

$tipo = $_GET['tipo'] ?? '';

$config = [
    "loja" => [
        "tabela"       => "avaliacoes_loja",
        "tabela_itens" => "lojas",
        "campo_item"   => "nome",
        "campo_nota"   => "nota_geral",
        "campo_item_fk"=> "loja_id"
    ],

    "setor" => [
        "tabela"       => "avaliacoes_setor",
        "tabela_itens" => "setores",
        "campo_item"   => "nome",
        "campo_nota"   => "nota_geral",
        "campo_item_fk"=> "setor_id"
    ],

    "equipe" => [
        "tabela"       => "avaliacoes_equipe",
        "tabela_itens" => "equipes",
        "campo_item"   => "nome",
        "campo_nota"   => "nota_geral",
        "campo_item_fk"=> "equipe_id"
    ]
];

if (!isset($config[$tipo])) {
    echo json_encode([]);
    exit;
}

$c = $config[$tipo];

$sql = "
    SELECT 
        a.id,
        i.{$c['campo_item']} AS item,
        a.{$c['campo_nota']} AS nota,
        a.data_avaliacao
    FROM {$c['tabela']} a
    INNER JOIN {$c['tabela_itens']} i ON i.id = a.{$c['campo_item_fk']}
    ORDER BY a.data_avaliacao DESC, a.id DESC
    LIMIT 10
";

$res = $conn->query($sql);

$lista = [];

while ($row = $res->fetch_assoc()) {
    $lista[] = [
        "id"   => $row["id"],
        "item" => $row["item"],
        "nota" => floatval($row["nota"]),
        "data" => $row["data_avaliacao"]
    ];
}

echo json_encode($lista);
