<?php
require_once '../dados/conexao.php';
$conn = conectar();

$id = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT id, nome, localizacao, descricao
    FROM lojas_dispositivos
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

echo json_encode($res);
