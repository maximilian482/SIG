<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/funcoes.php';
$conn = conectar();


$funcionarioId = intval($_GET['funcionario_id'] ?? 0);
$tipo = $_GET['tipo'] ?? 'geral'; // 🎂 ou 🏆
$usuarioId = $_SESSION['funcionario_id'] ?? $_SESSION['id_funcionario'] ?? null;

if (!$usuarioId) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado.']);
    exit;
}

$anoAtual = date('Y');
$mesAtual = date('n');

// Verifica se já existe reconhecimento do mesmo usuário para este funcionário neste mês e tipo
$sqlCheck = "SELECT id FROM reconhecimentos WHERE funcionario_id = ? AND usuario_id = ? AND ano = ? AND mes = ? AND tipo = ?";
$stmt = $conn->prepare($sqlCheck);
$stmt->bind_param("iiiis", $funcionarioId, $usuarioId, $anoAtual, $mesAtual, $tipo);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => "⚠️ Ops! Você já enviou um reconhecimento de {$tipo} para este colaborador neste mês."
    ]);
    exit;
}


// Insere novo reconhecimento
$sqlInsert = "INSERT INTO reconhecimentos (funcionario_id, usuario_id, ano, mes, tipo) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sqlInsert);
$stmt->bind_param("iiiis", $funcionarioId, $usuarioId, $anoAtual, $mesAtual, $tipo);

if ($stmt->execute()) {
    echo json_encode([
        'sucesso' => true,
        'mensagem' => "🎉 Reconhecimento de {$tipo} registrado com sucesso!"
    ]);
} else {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => "❌ Erro ao registrar reconhecimento."
    ]);
}

