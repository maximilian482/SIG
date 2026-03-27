<?php
require_once '../dados/conexao.php';
$conn = conectar();

$id = intval($_GET['id'] ?? 0);

header('Content-Type: application/json');

if ($id <= 0) {
    echo json_encode(['erro' => 'ID inválido.']);
    exit;
}

// Verificar se o cargo está em uso
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM funcionarios WHERE cargo_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if ($res['total'] > 0) {
    echo json_encode(['erro' => 'Este cargo está associado a funcionários e não pode ser excluído.']);
    exit;
}

// Excluir cargo
$stmt = $conn->prepare("DELETE FROM cargos WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Cargo excluído com sucesso!'
    ]);
    exit;
}

echo json_encode(['erro' => 'Erro ao excluir cargo: ' . $stmt->error]);
exit;
