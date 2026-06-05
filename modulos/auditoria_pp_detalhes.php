<?php
ob_clean();
header("Content-Type: application/json; charset=utf-8");
require_once '../dados/conexao.php';

$conn = conectar();
$id = intval($_GET["id"]);

// CABEÇALHO DA AUDITORIA
$sql = "
    SELECT 
        a.*, 
        l.nome AS loja,
        f.nome AS avaliador
    FROM auditoria_pp a
    JOIN lojas l ON l.id = a.loja_id
    LEFT JOIN funcionarios f ON f.id = a.avaliador_id
    WHERE a.id = $id
";

$res = $conn->query($sql);
$auditoria = $res->fetch_assoc();

// AJUSTE DO CAMINHO DA ASSINATURA
if (!empty($auditoria['assinatura'])) {
    if (!str_starts_with($auditoria['assinatura'], '/uploads')) {
        $auditoria['assinatura'] = '/uploads/assinaturas_pp/' . $auditoria['assinatura'];
    }
} else {
    $auditoria['assinatura'] = null;
}

// ITENS
$sqlItens = "
    SELECT pergunta, resposta, observacao
    FROM auditoria_pp_itens
    WHERE auditoria_id = $id
";

$itens = [];
$resItens = $conn->query($sqlItens);

while ($i = $resItens->fetch_assoc()) {
    $i['resposta'] = (int)$i['resposta'];
    $itens[] = $i;
}

echo json_encode([
    "auditoria" => $auditoria,
    "itens" => $itens
]);
