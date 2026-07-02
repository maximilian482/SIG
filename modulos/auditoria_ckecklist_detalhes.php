<?php
ob_clean();
header("Content-Type: application/json; charset=utf-8");
require_once '../dados/conexao.php';

$conn = conectar();
$id = intval($_GET["id"]);

// ===============================
// CABEÇALHO DA AUDITORIA
// ===============================
$sql = "
    SELECT 
        a.*, 
        l.nome AS loja,
        f.nome AS avaliador
    FROM auditoria_checklist a
    JOIN lojas l ON l.id = a.loja_id
    LEFT JOIN funcionarios f ON f.id = a.avaliador_id
    WHERE a.id = $id
";

$res = $conn->query($sql);
$auditoria = $res->fetch_assoc();

if (!$auditoria) {
    echo json_encode(["erro" => "Auditoria não encontrada"]);
    exit;
}

// ===============================
// AJUSTE DO CAMINHO DA ASSINATURA
// ===============================
if (!empty($auditoria['assinatura'])) {

    // Se já é base64, mantém
    if (str_starts_with($auditoria['assinatura'], 'data:image')) {
        // nada a fazer
    } 
    // Se for arquivo salvo, ajusta caminho
    elseif (!str_starts_with($auditoria['assinatura'], '/uploads')) {
        $auditoria['assinatura'] = '/uploads/assinaturas_checklist/' . $auditoria['assinatura'];
    }

} else {
    $auditoria['assinatura'] = null;
}

// ===============================
// ITENS DA AUDITORIA
// ===============================
$sqlItens = "
    SELECT pergunta, resposta, observacao
    FROM auditoria_checklist_itens
    WHERE auditoria_id = $id
    ORDER BY item_id ASC
";

$itens = [];
$resItens = $conn->query($sqlItens);

while ($i = $resItens->fetch_assoc()) {
    $i['resposta'] = (int)$i['resposta'];
    $itens[] = $i;
}

// ===============================
// RETORNO JSON
// ===============================
echo json_encode([
    "auditoria" => $auditoria,
    "itens" => $itens
]);
