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
$camposObrigatorios = ["loja_id", "avaliador_id", "responsavel_nome", "assinatura", "setores", "data_avaliacao"];

foreach ($camposObrigatorios as $campo) {
    if (!isset($data[$campo]) || $data[$campo] === "") {
        echo json_encode(["status" => "erro", "mensagem" => "Campo obrigatório ausente: $campo"]);
        exit;
    }
}

// ===============================
// VALIDAR DATA DA AVALIAÇÃO
// ===============================

$dataAvaliacao = $data["data_avaliacao"];

if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $dataAvaliacao)) {
    echo json_encode(["status" => "erro", "mensagem" => "Data da avaliação inválida"]);
    exit;
}

// ===============================
// SALVAR ASSINATURA
// ===============================

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

    $pasta = "../uploads/assinaturas";

    if (!is_dir($pasta)) {
        mkdir($pasta, 0777, true);
    }

    $nomeArquivo = "assinatura_loja_" . time() . "_" . rand(1000,9999) . ".png";
    $caminhoFinal = $pasta . "/" . $nomeArquivo;

    file_put_contents($caminhoFinal, $binario);
}

// ===============================
// CALCULAR NOTA GERAL
// ===============================

$notas = array_column($data["setores"], "nota_setor");

$notasValidas = array_filter($notas, function($n) {
    return $n !== null && $n >= 0;
});

$notaGeral = count($notasValidas) > 0 
    ? array_sum($notasValidas) / count($notasValidas)
    : 0;

// ===============================
// INSERIR AVALIAÇÃO PRINCIPAL
// ===============================

$sql = "INSERT INTO avaliacoes_loja 
        (loja_id, avaliador_id, responsavel_nome, data_avaliacao, assinatura, nota_geral, observacao_final)
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "iisssds",
    $data["loja_id"],
    $data["avaliador_id"],
    $data["responsavel_nome"],
    $dataAvaliacao,
    $nomeArquivo,
    $notaGeral,
    $data["observacao_final"]
);

if (!$stmt->execute()) {
    echo json_encode(["status" => "erro", "mensagem" => "Erro ao salvar avaliação"]);
    exit;
}

$avaliacaoId = $stmt->insert_id;

// ===============================
// INSERIR SETORES AVALIADOS
// ===============================

$sqlSetor = "INSERT INTO avaliacoes_setores 
             (avaliacao_id, setor_id, nota_setor, observacao)
             VALUES (?, ?, ?, ?)";

$stmtSetor = $conn->prepare($sqlSetor);

foreach ($data["setores"] as $setor) {

    $stmtSetor->bind_param(
        "iiis",
        $avaliacaoId,
        $setor["setor_id"],
        $setor["nota_setor"],
        $setor["observacao"]
    );
    $stmtSetor->execute();

    // PEGAR ID DO SETOR SALVO
    $avaliacaoSetorId = $stmtSetor->insert_id;

    // ===============================
    // SALVAR CRITÉRIOS INDIVIDUAIS
    // ===============================
    if (!empty($setor["criterios"])) {

        $sqlCrit = "INSERT INTO avaliacoes_setores_criterios 
                    (avaliacao_setor_id, criterio, valor)
                    VALUES (?, ?, ?)";

        $stmtCrit = $conn->prepare($sqlCrit);

        foreach ($setor["criterios"] as $crit) {
            $stmtCrit->bind_param(
                "isi",
                $avaliacaoSetorId,
                $crit["nome"],
                $crit["valor"]
            );
            $stmtCrit->execute();
        }
    }
}

// ===============================
// RESPOSTA FINAL
// ===============================

echo json_encode([
    "status" => "ok",
    "mensagem" => "Avaliação salva com sucesso",
    "avaliacao_id" => $avaliacaoId
]);
