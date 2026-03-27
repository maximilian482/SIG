<?php
require_once '../dados/conexao.php';
$conn = conectar();

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["erro" => "Setor inválido."]);
    exit;
}

$sql = "SELECT id, nome FROM setores WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["erro" => "Setor não encontrado."]);
    exit;
}

echo json_encode($res->fetch_assoc());
