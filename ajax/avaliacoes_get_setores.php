<?php
require_once '../dados/conexao.php';
$conn = conectar();

$loja = intval($_GET['loja_id']);

$sql = "
SELECT DISTINCT s.id, s.nome
FROM avaliacoes_setores a
JOIN setores s ON s.id = a.setor_id
JOIN avaliacoes_loja al ON al.id = a.avaliacao_id
WHERE al.loja_id = $loja
ORDER BY s.nome
";

$res = $conn->query($sql);

$dados = [];

while ($s = $res->fetch_assoc()) {
    $dados[] = $s;
}

echo json_encode($dados);
