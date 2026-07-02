<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

$cpf = $_SESSION['cpf'] ?? '';

if (!$cpf) {
    echo json_encode(['sucesso' => false, 'erro' => 'Sessão expirada']);
    exit;
}

// Buscar dados do usuário
$stmt = $conn->prepare("SELECT id, nome, loja_id FROM funcionarios WHERE cpf = ?");
$stmt->bind_param("s", $cpf);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    echo json_encode(['sucesso' => false, 'erro' => 'Usuário não encontrado']);
    exit;
}

$usuarioId   = intval($usuario['id']);
$lojaUsuario = intval($usuario['loja_id']);

// ===============================
// RECEBER JSON
// ===============================
$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(['sucesso' => false, 'erro' => 'Nenhum dado recebido']);
    exit;
}

$produto    = trim($input['produto'] ?? '');
$quantidade = floatval($input['quantidade'] ?? 0);
$motivo     = trim($input['motivo'] ?? '');
$urgencia   = $input['urgencia'] ?? 'baixa';

// ===============================
// VALIDAÇÃO
// ===============================
if (empty($produto) || $quantidade <= 0) {
    echo json_encode(['sucesso' => false, 'erro' => 'Preencha todos os campos obrigatórios']);
    exit;
}

// ===============================
// INSERIR SOLICITAÇÃO
// ===============================
$stmt = $conn->prepare("
    INSERT INTO compras_externas 
    (loja_id, solicitante_id, produto, quantidade, motivo, urgencia, status)
    VALUES (?, ?, ?, ?, ?, ?, 'aberto')
");

$stmt->bind_param(
    "iisdss",
    $lojaUsuario,
    $usuarioId,
    $produto,
    $quantidade,
    $motivo,
    $urgencia
);

if (!$stmt->execute()) {
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar solicitação']);
    exit;
}

$compraId = $stmt->insert_id;

// ===============================
// RETORNO
// ===============================
echo json_encode([
    'sucesso'   => true,
    'compra_id' => $compraId
]);
exit;
