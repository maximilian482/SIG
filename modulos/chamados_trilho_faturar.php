<?php
session_start();

require_once '../includes/funcoes.php';
require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json');

$conn = conectar();

// ===============================
// VALIDAR LOGIN
// ===============================
if (!isset($_SESSION['cpf'])) {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Sessão expirada. Faça login novamente."
    ]);
    exit;
}

// ===============================
// VALIDAR ID
// ===============================
$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Protocolo inválido."
    ]);
    exit;
}

// ===============================
// BUSCAR DADOS DO PROTOCOLO
// ===============================
$sql = "
    SELECT 
        t.id,
        t.protocolo,
        t.solicitado_id,
        t.status,
        t.descricao,
        lo.nome AS loja_origem,
        ld.nome AS loja_destino
    FROM chamados_trilho t
    LEFT JOIN lojas lo ON lo.id = t.loja_origem_id
    LEFT JOIN lojas ld ON ld.id = t.loja_destino_id
    WHERE t.id = {$id}
";

$dados = $conn->query($sql)->fetch_assoc();

if (!$dados) {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Protocolo não encontrado."
    ]);
    exit;
}

// ===============================
// VALIDAR STATUS
// ===============================
$status = strtolower(trim($dados['status'] ?? ''));

if ($status !== 'aberto') {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Este protocolo não está mais em status 'aberto'."
    ]);
    exit;
}

// ===============================
// PROCESSAR FATURAMENTO
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {

    $nota = trim($_REQUEST['nota_transferencia'] ?? '');

    if (empty($nota)) {
        echo json_encode([
            "sucesso" => false,
            "mensagem" => "Informe o número da nota de transferência."
        ]);
        exit;
    }

    $funcionarioId = intval($_SESSION['funcionario_id']);

    $stmt = $conn->prepare("
        UPDATE chamados_trilho
        SET nota_transferencia = ?, 
            status = 'faturado',
            data_faturamento = NOW(),
            faturado_por = ?
        WHERE id = ?
    ");

    $stmt->bind_param("sii", $nota, $funcionarioId, $id);

    if ($stmt->execute()) {
        echo json_encode([
            "sucesso" => true,
            "mensagem" => "Protocolo faturado com sucesso!"
        ]);
    } else {
        echo json_encode([
            "sucesso" => false,
            "mensagem" => "Erro ao faturar protocolo."
        ]);
    }

    exit;
}

?>
