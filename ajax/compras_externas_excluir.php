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

// Receber JSON
$input = json_decode(file_get_contents("php://input"), true);
$id = intval($input['id'] ?? 0);

if (!$id) {
    echo json_encode(['sucesso' => false, 'erro' => 'ID inválido']);
    exit;
}

// Buscar solicitação
$stmt = $conn->prepare("SELECT solicitante_id, status FROM compras_externas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$sol = $stmt->get_result()->fetch_assoc();

if (!$sol) {
    echo json_encode(['sucesso' => false, 'erro' => 'Solicitação não encontrada']);
    exit;
}

// Regra de exclusão
$usuarioPodeExcluir =
    $usuarioId == $sol['solicitante_id'] ||
    temAcesso($conn, $cpf, 'super') ||
    temAcesso($conn, $cpf, 'ceo');

if (!$usuarioPodeExcluir) {
    echo json_encode(['sucesso' => false, 'erro' => 'Você não tem permissão para excluir']);
    exit;
}

if ($sol['status'] !== 'aberto') {
    echo json_encode(['sucesso' => false, 'erro' => 'Somente solicitações em ABERTO podem ser excluídas']);
    exit;
}

// Excluir
$stmt = $conn->prepare("DELETE FROM compras_externas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

echo json_encode(['sucesso' => true]);
exit;
