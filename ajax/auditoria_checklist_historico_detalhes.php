<?php
require_once '../dados/conexao.php';
$conn = conectar();

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo "ID inválido";
    exit;
}

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
$aud = $res->fetch_assoc();

if (!$aud) {
    echo "Auditoria não encontrada.";
    exit;
}

$sqlItens = "
    SELECT pergunta, resposta, observacao
    FROM auditoria_checklist_itens
    WHERE auditoria_id = $id
    ORDER BY id ASC
";

$resItens = $conn->query($sqlItens);

/* Ajustar assinatura */
$assinatura = "";
if (!empty($aud['assinatura'])) {
    if (strpos($aud['assinatura'], 'data:image') === 0) {
        $assinatura = $aud['assinatura'];
    } else {
        $assinatura = "data:image/png;base64," . $aud['assinatura'];
    }
}

/* ============================
   HTML NOVO — COMPATÍVEL COM O CSS
============================ */
$html = "
<div class='his-detalhes'>

    <div class='his-header'>

        <div class='his-info'>
            <h3>{$aud['loja']}</h3>
            <p><strong>Data:</strong> " . date("d/m/Y", strtotime($aud['data_auditoria'])) . "</p>
            <p><strong>Responsável:</strong> {$aud['responsavel_nome']}</p>
            <p><strong>Avaliador:</strong> {$aud['avaliador_nome']}</p>

            <div class='his-nota-geral'>{$aud['nota_geral']}%</div>
        </div>

        <div class='his-assinatura-box'>
";

if ($assinatura) {
    $html .= "<img class='his-assinatura-img' src='{$assinatura}' alt='Assinatura'>";
} else {
    $html .= "<p>Sem assinatura registrada.</p>";
}

$html .= "
        </div>
    </div>

    <div class='his-itens'>
";

while ($i = $resItens->fetch_assoc()) {

    $classeNota = "his-nota-baixa";
    $classeBarra = "his-barra-baixa";

    if ($i['resposta'] == 50) {
        $classeNota = "his-nota-media";
        $classeBarra = "his-barra-media";
    }

    if ($i['resposta'] == 100) {
        $classeNota = "his-nota-alta";
        $classeBarra = "his-barra-alta";
    }

    $html .= "
        <div class='his-item'>
            <div class='his-pergunta'>{$i['pergunta']}</div>

            <div class='his-barra'>
                <div class='his-barra-fill {$classeBarra}' style='width: {$i['resposta']}%;'></div>
            </div>

            <div class='his-nota {$classeNota}'>{$i['resposta']}%</div>
    ";

    if (!empty($i['observacao'])) {
        $html .= "<div class='his-obs'>{$i['observacao']}</div>";
    }

    $html .= "</div>";
}

$html .= "</div></div>";

echo $html;
