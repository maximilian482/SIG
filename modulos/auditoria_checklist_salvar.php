<?php
require_once '../dados/conexao.php';
$conn = conectar();

header('Content-Type: application/json; charset=utf-8');

// ===============================
// RECEBER JSON
// ===============================
$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(['sucesso' => false, 'erro' => 'Nenhum dado recebido']);
    exit;
}

// ===============================
// CAPTURAR CAMPOS
// ===============================
$loja_id          = intval($input['loja_id']);
$avaliador_id     = intval($input['avaliador_id']);
$responsavel      = $conn->real_escape_string($input['responsavel_nome']);
$data_auditoria   = $conn->real_escape_string($input['data_auditoria']);
$observacao_final = $conn->real_escape_string($input['observacao_final']);
$assinatura       = $conn->real_escape_string($input['assinatura_base64']);
$respostas        = $input['respostas'];

// ===============================
// VALIDAÇÃO
// ===============================
if (!$loja_id || !$avaliador_id || empty($respostas)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos']);
    exit;
}

if (empty($data_auditoria)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Data da auditoria obrigatória']);
    exit;
}

if (empty($responsavel)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Responsável obrigatório']);
    exit;
}

if (empty($assinatura)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Assinatura obrigatória']);
    exit;
}

// ===============================
// CALCULAR NOTA GERAL
// ===============================
$total = 0;
$respondidos = 0;

foreach ($respostas as $r) {
    if ($r['valor'] !== null) {
        $total += intval($r['valor']);
        $respondidos++;
    }
}

$nota_geral = $respondidos > 0 ? round($total / $respondidos) : 0;

// ===============================
// SALVAR CABEÇALHO (CHECKLIST)
// ===============================
$sql = "
    INSERT INTO auditoria_checklist
    (loja_id, avaliador_id, responsavel_nome, data_auditoria, assinatura, observacao_final, nota_geral, criado_em)
    VALUES
    ($loja_id, $avaliador_id, '$responsavel', '$data_auditoria', '$assinatura', '$observacao_final', $nota_geral, NOW())
";

if (!$conn->query($sql)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar auditoria']);
    exit;
}

$auditoria_id = $conn->insert_id;

// ===============================
// SALVAR ITENS (CHECKLIST)
// ===============================
foreach ($respostas as $r) {

    $item_id   = intval($r['item_id']);
    $pergunta  = $conn->real_escape_string($r['pergunta']);
    $valor     = $r['valor'] !== null ? intval($r['valor']) : 'NULL';
    $obs       = $conn->real_escape_string($r['observacao']);

    $sqlItem = "
        INSERT INTO auditoria_checklist_itens
        (auditoria_id, item_id, pergunta, resposta, observacao, criado_em)
        VALUES
        ($auditoria_id, $item_id, '$pergunta', $valor, '$obs', NOW())
    ";

    $conn->query($sqlItem);
}

// ===============================
// RETORNO
// ===============================
echo json_encode([
    'sucesso' => true,
    'auditoria_id' => $auditoria_id,
    'nota_geral' => $nota_geral
]);
exit;
