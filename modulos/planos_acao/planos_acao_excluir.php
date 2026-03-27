<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';

$conn = conectar();

// Exigir método POST para excluir
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Método inválido para exclusão.');
    header("Location: planos_acao_listar.php");
    exit;
}

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    setFlash('error', 'Plano inválido.');
    header("Location: planos_acao_listar.php");
    exit;
}

// Verificar se o plano existe
$sqlCheck = "SELECT id FROM planos_acao WHERE id = ? LIMIT 1";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("i", $id);
$stmtCheck->execute();
$result = $stmtCheck->get_result();
$stmtCheck->close();

if ($result->num_rows === 0) {
    setFlash('error', 'Plano não encontrado.');
    header("Location: planos_acao_listar.php");
    exit;
}

// 1) Excluir tarefas do plano
$sqlT = "DELETE FROM tarefas_plano WHERE id_plano = ?";
$stmtT = $conn->prepare($sqlT);
$stmtT->bind_param("i", $id);
$stmtT->execute();
$stmtT->close();

// 2) Excluir o plano
$sqlP = "DELETE FROM planos_acao WHERE id = ? LIMIT 1";
$stmtP = $conn->prepare($sqlP);
$stmtP->bind_param("i", $id);
$stmtP->execute();
$stmtP->close();

// Mensagem de sucesso
setFlash('success', 'Plano de Ação excluído com sucesso!');

// Redirecionar
header("Location: planos_acao_listar.php");
exit;
