<?php
ob_clean();
header("Content-Type: application/json; charset=utf-8");
require_once '../dados/conexao.php';

$conn = conectar();
$id = intval($_GET["id"]);

// Exclui itens da auditoria
$conn->query("DELETE FROM auditoria_checklist_itens WHERE auditoria_id = $id");

// Exclui auditoria principal
$conn->query("DELETE FROM auditoria_checklist WHERE id = $id");

echo json_encode(["status" => "ok"]);
