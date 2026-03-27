<?php
require_once __DIR__ . '/../dados/conexao.php';
session_start();

$remetenteId   = $_SESSION['id_funcionario'] ?? null;
$funcionarioId = $_POST['funcionario_id'] ?? null;
$tipo          = $_POST['tipo'] ?? null;

if (!$remetenteId || !$funcionarioId || !$tipo) {
    http_response_code(400);
    echo "Dados inválidos.";
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO interacoes (funcionario_id, tipo, remetente_id)
    VALUES (?, ?, ?)
");
$stmt->bind_param("isi", $funcionarioId, $tipo, $remetenteId);

if ($stmt->execute()) {
    echo "Interação registrada com sucesso!";
} else {
    http_response_code(500);
    echo "Erro ao registrar interação: " . $stmt->error;
}
