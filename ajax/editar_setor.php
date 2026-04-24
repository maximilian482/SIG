<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

$id = intval($_POST['id'] ?? 0);
$nome = trim($_POST['nome'] ?? '');

if ($id <= 0 || $nome === '') {
    exit("Erro");
}

$sql = "UPDATE setores_padrao SET nome_setor = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $nome, $id);
$stmt->execute();

echo "OK";
