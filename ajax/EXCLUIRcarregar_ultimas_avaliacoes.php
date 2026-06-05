<?php
require_once '../dados/conexao.php';
$conn = conectar();

$sql = "SELECT a.id, a.data_avaliacao, a.nota_geral, l.nome AS loja
        FROM avaliacoes_loja a
        JOIN lojas l ON l.id = a.loja_id
        ORDER BY a.data_avaliacao DESC
        LIMIT 10";

$res = $conn->query($sql);

$avaliacoes = [];

while ($row = $res->fetch_assoc()) {
    $avaliacoes[] = $row;
}

echo json_encode($avaliacoes);
