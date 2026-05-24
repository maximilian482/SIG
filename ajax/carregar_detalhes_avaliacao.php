<?php
require_once '../dados/conexao.php';
$conn = conectar();

$id = intval($_GET["id"]);

$sql = "SELECT 
            a.*, 
            l.nome AS loja,
            f.nome AS avaliador_nome
        FROM avaliacoes_loja a
        JOIN lojas l ON l.id = a.loja_id
        LEFT JOIN funcionarios f ON f.id = a.avaliador_id
        WHERE a.id = $id";

$res = $conn->query($sql);
$avaliacao = $res->fetch_assoc();

/* 🔥 CORREÇÃO DO CAMINHO DA ASSINATURA */
if (!empty($avaliacao['assinatura'])) {

    if (str_starts_with($avaliacao['assinatura'], '/uploads')) {
        $avaliacao['assinatura'] = $avaliacao['assinatura'];
    } else {
        $avaliacao['assinatura'] = '/uploads/assinaturas/' . $avaliacao['assinatura'];
    }

} else {
    $avaliacao['assinatura'] = null;
}
/* 🔥 FIM DA CORREÇÃO */

// ===============================
// BUSCAR SETORES AVALIADOS
// ===============================
$sql2 = "SELECT 
            av.id AS avaliacao_setor_id,
            sp.nome_setor AS setor,
            av.nota_setor,
            av.observacao
         FROM avaliacoes_setores av
         LEFT JOIN setores_padrao sp ON sp.id = av.setor_id
         WHERE av.avaliacao_id = $id";

$res2 = $conn->query($sql2);

$setores = [];
while ($row = $res2->fetch_assoc()) {

    $avaliacaoSetorId = $row['avaliacao_setor_id'];

    // ===============================
    // BUSCAR CRITÉRIOS DO SETOR
    // ===============================
    $sqlCrit = "SELECT criterio, valor 
                FROM avaliacoes_setores_criterios
                WHERE avaliacao_setor_id = $avaliacaoSetorId";

    $resCrit = $conn->query($sqlCrit);

    $criterios = [];
    while ($c = $resCrit->fetch_assoc()) {
        $criterios[] = $c;
    }

    $row['criterios'] = $criterios;

    $setores[] = $row;
}

echo json_encode([
    "avaliacao" => $avaliacao,
    "setores" => $setores
]);
