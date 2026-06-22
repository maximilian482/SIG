<?php
require_once '../dados/conexao.php';
$conn = conectar();

header("Content-Type: text/html; charset=utf-8");

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo "<div class='detalhes-erro'>ID inválido.</div>";
    exit;
}

/*
---------------------------------------------------------
1) CABEÇALHO
---------------------------------------------------------
*/
$sql = "
    SELECT 
        a.*, 
        l.nome AS loja,
        f.nome AS avaliador_nome
    FROM auditoria_pp a
    JOIN lojas l ON l.id = a.loja_id
    LEFT JOIN funcionarios f ON f.id = a.avaliador_id
    WHERE a.id = $id
";

$res = $conn->query($sql);

if (!$res || $res->num_rows === 0) {
    echo "<div class='detalhes-erro'>Auditoria não encontrada.</div>";
    exit;
}

$aud = $res->fetch_assoc();

/*
---------------------------------------------------------
2) ASSINATURA
---------------------------------------------------------
*/
$assinatura = "";

if (!empty($aud['assinatura'])) {
    if (strpos($aud['assinatura'], 'data:image') === 0) {
        $assinatura = "<img src='{$aud['assinatura']}' class='his-assinatura-img'>";
    } else {
        $assinatura = "<img src='/uploads/assinaturas/{$aud['assinatura']}' class='his-assinatura-img'>";
    }
}

/*
---------------------------------------------------------
3) ITENS + BARRAS (NAMESPACE his-)
---------------------------------------------------------
*/
$sqlItens = "
    SELECT pergunta, resposta, observacao
    FROM auditoria_pp_itens
    WHERE auditoria_id = $id
    ORDER BY id
";

$resItens = $conn->query($sqlItens);

$htmlItens = "";

while ($i = $resItens->fetch_assoc()) {

    $valor = intval($i['resposta']);
    $obs   = trim($i['observacao'] ?? "");

    // Classes de cor
    $classeNota  = "his-nota-media";
    $classeBarra = "media";

    if ($valor >= 90) {
        $classeNota  = "his-nota-alta";
        $classeBarra = "alta";
    } elseif ($valor < 70) {
        $classeNota  = "his-nota-baixa";
        $classeBarra = "baixa";
    }

    $htmlItens .= "
        <div class='his-item'>
            <div class='his-pergunta'>{$i['pergunta']}</div>

            <div class='his-nota {$classeNota}'>{$valor}%</div>

            <div class='his-barra'>
                <div class='his-barra-fill his-barra-{$classeBarra}' style='width: {$valor}%;'></div>
            </div>

            " . ($obs ? "<div class='his-obs'>Obs: {$obs}</div>" : "") . "
        </div>
    ";
}

/*
---------------------------------------------------------
4) HTML FINAL
---------------------------------------------------------
*/
echo "
<div class='his-detalhes'>

    <div class='his-header'>

        <div class='his-info'>
            <div><strong>Loja:</strong> {$aud['loja']}</div>
            <div><strong>Data:</strong> " . date('d/m/Y', strtotime($aud['data_auditoria'])) . "</div>
            <div><strong>Responsável:</strong> {$aud['responsavel_nome']}</div>
            <div><strong>Avaliador:</strong> " . ($aud['avaliador_nome'] ?? '—') . "</div>

            <div class='his-nota-geral'>{$aud['nota_geral']}%</div>
        </div>

        <div class='his-assinatura-box'>
            {$assinatura}
        </div>

    </div>

    <div class='his-itens'>
        {$htmlItens}
    </div>

</div>
";
