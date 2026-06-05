<?php
require_once '../dados/conexao.php';
$conn = conectar();

$pergunta = $_POST['pergunta'] ?? '';
$global = intval($_POST['global'] ?? 0);
$lojaId = $_POST['loja_id'] ?? null;

if (!$pergunta) {
    echo json_encode(["erro" => "Pergunta inválida"]);
    exit;
}

$lojaFinal = $global ? null : $lojaId;

$sql = "INSERT INTO auditoria_pp_config (pergunta, loja_id) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $pergunta, $lojaFinal);
$stmt->execute();

echo json_encode([
    "id" => $stmt->insert_id,
    "pergunta" => $pergunta,
    "global" => $global
]);
