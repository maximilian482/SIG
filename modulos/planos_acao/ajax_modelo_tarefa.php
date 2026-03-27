<?php
require_once __DIR__ . '/../../includes/funcoes.php';

$conn = conectar();

$id = intval($_GET['id'] ?? 0);

$sql = "SELECT titulo, descricao FROM tarefas_modelo WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

header('Content-Type: application/json');
echo json_encode($res ?: []);
