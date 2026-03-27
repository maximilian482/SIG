<?php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';
require_once __DIR__ . '/../../../config/bootstrap.php';

$conn = conectar();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die("ID inválido.");

$sql = "DELETE FROM tarefas_modelo WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: tarefas_modelo_listar.php");
exit;
