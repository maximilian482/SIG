<?php
header("Content-Type: application/json");
require_once '../dados/conexao.php';

$conn = conectar();
if (!$conn) {
    echo json_encode(["status" => "erro", "mensagem" => "Falha na conexão com o banco"]);
    exit;
}

// Receber JSON
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(["status" => "erro", "mensagem" => "JSON inválido"]);
    exit;
}

// Validar campos obrigatórios
$obrigatorios = ["loja_id", "avaliador_id", "responsavel_nome", "assinatura", "data_auditoria", "itens"];

foreach ($obrigatorios as $campo) {
    if (!isset($data[$campo]) || $data[$campo] === "") {
        echo json_encode(["status" => "erro", "mensagem" => "Campo obrigatório ausente: $campo"]);
        exit;
    }
}

// Validar data
$dataAuditoria = $data["data_auditoria"];
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $dataAuditoria)) {
    echo json_encode(["status" => "erro", "mensagem" => "Data inválida"]);
    exit;
}

// ==========================================================
// SALVAR ASSINATURA
// ==========================================================
$assinatura_base64 = $data["assinatura"];
$nomeArquivo = null;

if (!empty($assinatura_base64)) {

    $assinatura_base64 = str_replace("data:image/png;base64,", "", $assinatura_base64);
    $assinatura_base64 = str_replace(" ", "+", $assinatura_base64);

    $binario = base64_decode($assinatura_base64);

    if (!$binario) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao processar assinatura"]);
        exit;
    }

    $pasta = "../uploads/assinaturas_pp";

    if (!is_dir($pasta)) {
        mkdir($pasta, 0777, true);
    }

    $nomeArquivo = "assinatura_pp_" . time() . "_" . rand(1000,9999) . ".png";
    $caminhoFinal = $pasta . "/" . $nomeArquivo;

    file_put_contents($caminhoFinal, $binario);
}

// ==========================================================
// CALCULAR NOTA GERAL
// ==========================================================
$notas = array_column($data["itens"], "resposta");

$notasValidas = array_filter($notas, function($n) {
    return $n !== null && $n >= 0;
});

$notaGeral = count($notasValidas) > 0 
    ? array_sum($notasValidas) / count($notasValidas)
    : 0;

// ==========================================================
// INSERIR AUDITORIA PRINCIPAL
// ==========================================================
$sql = "INSERT INTO auditoria_pp 
        (loja_id, avaliador_id, responsavel_nome, data_auditoria, assinatura, nota_geral, observacao_final)
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "iisssds",
    $data["loja_id"],
    $data["avaliador_id"],
    $data["responsavel_nome"],
    $dataAuditoria,
    $nomeArquivo,
    $notaGeral,
    $data["observacao_final"]
);

if (!$stmt->execute()) {
    echo json_encode(["status" => "erro", "mensagem" => "Erro ao salvar auditoria"]);
    exit;
}

$auditoriaId = $stmt->insert_id;

// ==========================================================
// INSERIR ITENS AVALIADOS
// ==========================================================
// TABELA NÃO TEM COLUNA "nota" → REMOVIDA DO INSERT

$sqlItem = "INSERT INTO auditoria_pp_itens 
            (auditoria_id, item_id, pergunta, resposta, observacao)
            VALUES (?, ?, ?, ?, ?)";

$stmtItem = $conn->prepare($sqlItem);

foreach ($data["itens"] as $item) {

    $stmtItem->bind_param(
        "iisis",
        $auditoriaId,
        $item["item_id"],
        $item["pergunta"],
        $item["resposta"],
        $item["observacao"]
    );

    $stmtItem->execute();
}

// ==========================================================
// RESPOSTA FINAL
// ==========================================================
echo json_encode([
    "status" => "ok",
    "mensagem" => "Auditoria salva com sucesso",
    "auditoria_id" => $auditoriaId
]);
