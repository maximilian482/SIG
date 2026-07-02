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

$id         = intval($input['id'] ?? 0);
$tipoCompra = $input['tipo_compra'] ?? '';

if (!$id || !in_array($tipoCompra, ['com_nota', 'sem_nota'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos']);
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

// Regra de alteração
$usuarioPodeAlterar =
    $usuarioId == $sol['solicitante_id'] ||
    temAcesso($conn, $cpf, 'super') ||
    temAcesso($conn, $cpf, 'ceo');

if (!$usuarioPodeAlterar) {
    echo json_encode(['sucesso' => false, 'erro' => 'Você não tem permissão para alterar']);
    exit;
}

if ($sol['status'] === 'concluido') {
    echo json_encode(['sucesso' => false, 'erro' => 'Não é possível alterar uma compra concluída']);
    exit;
}

// Montar SQL conforme tipo
if ($tipoCompra === 'com_nota') {
    $numero_nota   = $input['numero_nota'] ?? '';
    $data_compra   = $input['data_compra'] ?? null;
    $valor         = $input['valor'] ?? null;
    $local_compra  = $input['local_compra'] ?? '';
    $observacoes   = $input['observacoes'] ?? '';

    $stmt = $conn->prepare("
        UPDATE compras_externas
        SET tipo_compra = 'com_nota',
            numero_nota = ?,
            data_compra = ?,
            valor = ?,
            local_compra = ?,
            observacoes = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssdssi", $numero_nota, $data_compra, $valor, $local_compra, $observacoes, $id);

} else { // sem_nota
    $data_compra        = $input['data_compra'] ?? null;
    $hora_ajuste        = $input['hora_ajuste'] ?? null;
    $quantidade_ajustada = $input['quantidade_ajustada'] ?? null;
    $observacoes        = $input['observacoes'] ?? '';

    $stmt = $conn->prepare("
        UPDATE compras_externas
        SET tipo_compra = 'sem_nota',
            data_compra = ?,
            hora_ajuste = ?,
            quantidade_ajustada = ?,
            observacoes = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssisi", $data_compra, $hora_ajuste, $quantidade_ajustada, $observacoes, $id);
}

$stmt->execute();

echo json_encode(['sucesso' => true]);
exit;
