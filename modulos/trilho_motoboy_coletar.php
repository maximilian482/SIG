<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

header('Content-Type: application/json');

// ===============================
// VERIFICA LOGIN
// ===============================
if (!isset($_SESSION['cpf'])) {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Acesso negado."
    ]);
    exit;
}

$motoboyId = intval($_SESSION['funcionario_id'] ?? 0);
$cpf       = $_SESSION['cpf'];

// ===============================
// VERIFICA PERMISSÃO TRILHO
// ===============================
require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

if (!temAcesso($conn, $cpf, 'trilho_motoboy')) {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Você não tem permissão para coletar protocolos."
    ]);
    exit;
}

// ===============================
// VALIDA ID
// ===============================
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "ID inválido."
    ]);
    exit;
}

// ===============================
// BUSCAR PROTOCOLO
// ===============================
$sql = "
    SELECT id, status
    FROM chamados_trilho
    WHERE id = {$id}
";

$dados = $conn->query($sql)->fetch_assoc();

if (!$dados) {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Protocolo não encontrado."
    ]);
    exit;
}

if ($dados['status'] !== 'faturado') {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Somente protocolos faturados podem ser coletados."
    ]);
    exit;
}

// ===============================
// REGISTRAR COLETA
// ===============================
$stmt = $conn->prepare("
    UPDATE chamados_trilho
    SET status = 'em_rota',
        motoboy_id = ?,
        data_coleta = NOW()
    WHERE id = ?
");

$stmt->bind_param("ii", $motoboyId, $id);

if ($stmt->execute()) {
    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Coleta registrada com sucesso!"
    ]);
} else {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Erro ao registrar coleta."
    ]);
}

exit;
