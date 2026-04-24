<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) exit("Erro");

$sql = "DELETE FROM setores_padrao WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

echo "OK";
