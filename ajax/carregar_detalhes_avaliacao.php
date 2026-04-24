<?php
require_once '../dados/conexao.php';
$conn = conectar();

$id = intval($_GET["id"]);

$sql = "SELECT a.*, l.nome AS loja
        FROM avaliacoes_loja a
        JOIN lojas l ON l.id = a.loja_id
        WHERE a.id = $id";

$res = $conn->query($sql);
$avaliacao = $res->fetch_assoc();

$sql2 = "SELECT 
            sp.nome_setor AS setor,
            av.nota_setor,
            av.observacao
         FROM avaliacoes_setores av
         LEFT JOIN setores_padrao sp ON sp.id = av.setor_id
         WHERE av.avaliacao_id = $id";



$res2 = $conn->query($sql2);

$setores = [];
while ($row = $res2->fetch_assoc()) {
    $setores[] = $row;
}

echo json_encode([
    "avaliacao" => $avaliacao,
    "setores" => $setores
]);
