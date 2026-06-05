<?php
require_once '../dados/conexao.php';
$conn = conectar();

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["erro" => "ID inválido"]);
    exit;
}

/*
    1) Carregar dados gerais da auditoria
*/
$sql = "
    SELECT 
        a.id,
        l.nome AS loja,
        a.data_auditoria AS data,
        a.responsavel_nome AS responsavel,
        f.nome AS avaliador,
        a.assinatura,
        a.observacao_final,
        a.nota_geral
    FROM auditoria_pp a
    INNER JOIN lojas l ON l.id = a.loja_id
    LEFT JOIN funcionarios f ON f.id = a.avaliador_id
    WHERE a.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["erro" => "Auditoria não encontrada"]);
    exit;
}

$auditoria = $res->fetch_assoc();

/*
    2) Carregar itens avaliados
*/
$sqlItens = "
    SELECT 
        i.pergunta,
        i.resposta AS nota,
        i.observacao
    FROM auditoria_pp_itens i
    WHERE i.auditoria_id = ?
    ORDER BY i.id
";

$stmt2 = $conn->prepare($sqlItens);
$stmt2->bind_param("i", $id);
$stmt2->execute();
$res2 = $stmt2->get_result();

$itens = [];
while ($row = $res2->fetch_assoc()) {

    // Converter nota para porcentagem
    $nota = intval($row["nota"]);
    if ($nota === 10) $nota = 100;
    elseif ($nota === 5) $nota = 50;
    elseif ($nota === 0) $nota = 0;

    $itens[] = [
        "pergunta"   => $row["pergunta"],
        "nota"       => $nota,
        "observacao" => $row["observacao"]
    ];
}

/*
    3) Montar resposta JSON
*/
echo json_encode([
    "auditoria" => [
        "id"          => $auditoria["id"],
        "loja"        => $auditoria["loja"],
        "data"        => date("d/m/Y", strtotime($auditoria["data"])),
        "responsavel" => $auditoria["responsavel"],
        "avaliador"   => $auditoria["avaliador"],
        "assinatura"  => $auditoria["assinatura"],
        "observacao"  => $auditoria["observacao_final"],
        "nota_geral"  => floatval($auditoria["nota_geral"])
    ],
    "itens" => $itens
]);
