<?php
require_once '../dados/conexao.php';
$conn = conectar();

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo "ID inválido";
    exit;
}

// Excluir itens
$conn->query("DELETE FROM auditoria_checklist_itens WHERE auditoria_id = $id");

// Excluir auditoria
$conn->query("DELETE FROM auditoria_checklist WHERE id = $id");

echo "OK";
