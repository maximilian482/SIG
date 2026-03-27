<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/funcoes.php';
$conn = conectar();

$funcionarioId = (int)($_GET['funcionario_id'] ?? 0);
$tipo          = trim($_GET['tipo'] ?? '');
$usuarioId     = (int)($_SESSION['funcionario_id'] ?? $_SESSION['id_funcionario'] ?? 0);

if ($usuarioId <= 0) {
  echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão não reconhecida', 'usuario_id' => 0]);
  exit;
}

if ($funcionarioId <= 0 || empty($tipo)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos para reconhecimento.']);
    exit;
}

$anoAtual = date('Y');
$mesAtual = date('n');

// Verifica se já existe reconhecimento do mesmo usuário para este funcionário neste mês e tipo
$sqlCheck = "
    SELECT id 
    FROM reconhecimentos 
    WHERE funcionario_id = ? 
      AND usuario_id = ? 
      AND ano = ? 
      AND mes = ? 
      AND tipo = ?
";
$stmt = $conn->prepare($sqlCheck);
$stmt->bind_param("iiiis", $funcionarioId, $usuarioId, $anoAtual, $mesAtual, $tipo);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    echo json_encode([
        'sucesso' => false,
        'mensagem' => "⚠️ Você já enviou um reconhecimento de {$tipo} para este colaborador neste mês."
    ]);
    exit;
}
$stmt->close();

// Insere novo reconhecimento
$sqlInsert = "
    INSERT INTO reconhecimentos (funcionario_id, usuario_id, ano, mes, data, tipo) 
    VALUES (?, ?, ?, ?, NOW(), ?)
";
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
$stmt->close();
