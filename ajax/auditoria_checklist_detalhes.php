<?php
require_once '../dados/conexao.php';
$conn = conectar();

header("Content-Type: application/json");

if (!isset($_GET['id'])) {
    echo json_encode(["erro" => "ID não informado"]);
    exit;
}

$id = intval($_GET['id']);

/*
---------------------------------------------------------
1) BUSCAR CABEÇALHO DA AUDITORIA
---------------------------------------------------------
*/
$sql = "
    SELECT 
        a.*, 
        l.nome AS loja,
        f.nome AS avaliador_nome
    FROM auditoria_checklist a
    JOIN lojas l ON l.id = a.loja_id
    LEFT JOIN funcionarios f ON f.id = a.avaliador_id
    WHERE a.id = $id
";

$res = $conn->query($sql);

if (!$res || $res->num_rows === 0) {
    echo json_encode(["erro" => "Auditoria não encontrada"]);
    exit;
}

$aud = $res->fetch_assoc();

/*
---------------------------------------------------------
2) AJUSTAR ASSINATURA
---------------------------------------------------------
*/
$assinatura = null;

if (!empty($aud['assinatura'])) {

    // Se já tem prefixo base64, mantém
    if (strpos($aud['assinatura'], 'data:image') === 0) {
        $assinatura = $aud['assinatura'];
    } 
    // Se é base64 sem prefixo, adiciona
    elseif (preg_match('/^[A-Za-z0-9+\/=]+$/', $aud['assinatura'])) {
        $assinatura = 'data:image/png;base64,' . $aud['assinatura'];
    }
    // Se for arquivo (caso raro), monta caminho
    else {
        $assinatura = '/uploads/assinaturas_checklist/' . $aud['assinatura'];
    }
}


/*
---------------------------------------------------------
3) BUSCAR ITENS RESPONDIDOS
---------------------------------------------------------
*/
$sqlItens = "
    SELECT pergunta, resposta, observacao
    FROM auditoria_checklist_itens
    WHERE auditoria_id = $id
    ORDER BY id
";

$resItens = $conn->query($sqlItens);

$setores = [];

while ($i = $resItens->fetch_assoc()) {

    $valor = intval($i['resposta']);

    $setores[] = [
        "setor" => $i['pergunta'],
        "nota_setor" => $valor,
        "observacao" => $i['observacao'] ?? "",
        "criterios" => [
            [
                "criterio" => $i['pergunta'],
                "valor" => $valor
            ]
        ]
    ];
}

/*
---------------------------------------------------------
4) RETORNO FINAL — FORMATO COMPATÍVEL
---------------------------------------------------------
*/
echo json_encode([
    "avaliacao" => [
        "loja" => $aud['loja'],
        "data_avaliacao" => $aud['data_auditoria'],
        "responsavel_nome" => $aud['responsavel_nome'],
        "avaliador_nome" => $aud['avaliador_nome'] ?? "—",
        "nota_geral" => floatval($aud['nota_geral']),
        "assinatura" => $assinatura
    ],
    "setores" => $setores
]);
