<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

header('Content-Type: application/json; charset=utf-8');

$cpf = $_SESSION['cpf'] ?? '';

if (!$cpf) {
    echo json_encode(['sucesso' => false, 'erro' => 'Sessão expirada']);
    exit;
}

// Buscar usuário
$stmt = $conn->prepare("SELECT id FROM funcionarios WHERE cpf = ?");
$stmt->bind_param("s", $cpf);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

$usuarioId = $usuario['id'] ?? 0;

$id   = intval($_POST['id'] ?? 0);
$tipo = $_POST['tipo'] ?? '';

if (!$id || !$tipo) {
    echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos']);
    exit;
}

// Buscar solicitação
$stmt = $conn->prepare("SELECT solicitante_id FROM compras_externas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$sol = $stmt->get_result()->fetch_assoc();

if (!$sol) {
    echo json_encode(['sucesso' => false, 'erro' => 'Solicitação não encontrada']);
    exit;
}

// Regra de upload
$usuarioPodeEnviar =
    $usuarioId == $sol['solicitante_id'] ||
    temAcesso($conn, $cpf, 'super') ||
    temAcesso($conn, $cpf, 'ceo');

if (!$usuarioPodeEnviar) {
    echo json_encode(['sucesso' => false, 'erro' => 'Você não tem permissão para enviar anexos']);
    exit;
}

if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['sucesso' => false, 'erro' => 'Arquivo inválido']);
    exit;
}

$arquivo = $_FILES['arquivo'];

$nomeOriginal = $arquivo['name'];
$extensao     = pathinfo($nomeOriginal, PATHINFO_EXTENSION);
$nomeFinal    = 'compra_' . $id . '_' . time() . '.' . $extensao;

$diretorio = ROOT_PATH . '/uploads/compras_externas/';

if (!is_dir($diretorio)) {
    mkdir($diretorio, 0777, true);
}

$caminhoFinal = $diretorio . $nomeFinal;

if (!move_uploaded_file($arquivo['tmp_name'], $caminhoFinal)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar arquivo']);
    exit;
}

// Registrar no banco (tabela anexos_compras_externas, por exemplo)
$stmt = $conn->prepare("
    INSERT INTO compras_externas_anexos (compra_id, tipo, arquivo, criado_em)
    VALUES (?, ?, ?, NOW())
");
$stmt->bind_param("iss", $id, $tipo, $nomeFinal);
$stmt->execute();

echo json_encode(['sucesso' => true]);
exit;
