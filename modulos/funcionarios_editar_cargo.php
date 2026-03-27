<?php
require_once '../dados/conexao.php';
$conn = conectar();

$id = intval($_POST['id'] ?? 0);
$nome = trim($_POST['nome'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');

header('Content-Type: application/json');

// ===============================
// VALIDAÇÕES
// ===============================

if ($id <= 0) {
    echo json_encode(['erro' => 'ID inválido.']);
    exit;
}

if ($nome === '') {
    echo json_encode(['erro' => 'O nome do cargo é obrigatório.']);
    exit;
}

if (strlen($nome) < 2) {
    echo json_encode(['erro' => 'O nome do cargo é muito curto.']);
    exit;
}

if (strlen($nome) > 50) {
    echo json_encode(['erro' => 'O nome do cargo é muito longo.']);
    exit;
}

if (!preg_match('/^[A-Za-zÀ-ÿ0-9\s\-]+$/', $nome)) {
    echo json_encode(['erro' => 'O nome contém caracteres inválidos.']);
    exit;
}

// Impedir duplicidade
$stmt = $conn->prepare("SELECT id FROM cargos WHERE nome_cargo = ? AND id != ?");
$stmt->bind_param("si", $nome, $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['erro' => 'Já existe outro cargo com esse nome.']);
    exit;
}

// ===============================
// ATUALIZAR
// ===============================
$stmt = $conn->prepare("UPDATE cargos SET nome_cargo = ?, descricao = ? WHERE id = ?");
$stmt->bind_param("ssi", $nome, $descricao, $id);

if ($stmt->execute()) {
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Cargo atualizado com sucesso!'
    ]);
    exit;
}

echo json_encode(['erro' => 'Erro ao atualizar cargo: ' . $stmt->error]);
exit;
