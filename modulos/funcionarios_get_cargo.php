<?php
require_once '../dados/conexao.php';
$conn = conectar();

$id = intval($_GET['id'] ?? 0);

header('Content-Type: application/json');

if ($id <= 0) {
    echo json_encode(['erro' => 'ID inválido']);
    exit;
}

$stmt = $conn->prepare("SELECT id, nome_cargo, descricao FROM cargos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['erro' => 'Cargo não encontrado']);
    exit;
}

$cargo = $result->fetch_assoc();

echo json_encode([
    'id' => $cargo['id'],
    'nome' => $cargo['nome_cargo'],
    'descricao' => $cargo['descricao']
]);
exit;
