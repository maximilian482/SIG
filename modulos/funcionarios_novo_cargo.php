<?php
require_once '../dados/conexao.php';
$conn = conectar();

$nome = trim($_POST['nome'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');

header('Content-Type: application/json');

// ===============================
// VALIDAÇÕES
// ===============================

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
$stmt = $conn->prepare("SELECT id FROM cargos WHERE nome_cargo = ?");
$stmt->bind_param("s", $nome);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['erro' => 'Já existe um cargo com esse nome.']);
    exit;
}

// ===============================
// INSERIR
// ===============================
$stmt = $conn->prepare("INSERT INTO cargos (nome_cargo, descricao) VALUES (?, ?)");
$stmt->bind_param("ss", $nome, $descricao);

if ($stmt->execute()) {
    echo json_encode([
        'sucesso' => true,
        'id' => $stmt->insert_id,
        'nome' => $nome,
        'mensagem' => 'Cargo criado com sucesso!'
    ]);
    exit;
}

echo json_encode(['erro' => 'Erro ao salvar cargo: ' . $stmt->error]);
exit;
