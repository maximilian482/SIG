<?php
require_once __DIR__ . '/../dados/conexao.php';
$conn = conectar();

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["erro" => "ID inválido"]);
    exit;
}

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
