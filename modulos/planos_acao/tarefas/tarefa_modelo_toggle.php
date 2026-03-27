<?php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';

$conn = conectar();

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID inválido.");
}

// Buscar status atual
$sql = "SELECT ativo FROM tarefas_modelo WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) {
    die("Modelo não encontrado.");
}

$novoStatus = $res['ativo'] ? 0 : 1;

// Atualizar status
$sqlUp = "UPDATE tarefas_modelo SET ativo = ? WHERE id = ?";
$stmtUp = $conn->prepare($sqlUp);
$stmtUp->bind_param("ii", $novoStatus, $id);
$stmtUp->execute();

// Redirecionar de volta
header("Location: tarefas_modelo_listar.php");
exit;
