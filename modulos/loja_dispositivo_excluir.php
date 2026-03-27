<?php
require_once '../dados/conexao.php';
$conn = conectar();

$id = intval($_GET['id']);
$loja = intval($_GET['loja']);

$stmt = $conn->prepare("DELETE FROM lojas_dispositivos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: loja.php?id=$loja&aba=dispositivos");
exit;
