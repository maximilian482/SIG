<?php
require_once '../dados/conexao.php';

$conn = conectar();

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    echo "ID inválido";
    exit;
}

// Remove ativações
$conn->query("DELETE FROM auditoria_checklist_config_ativos WHERE item_id = $id");

// Remove item
$conn->query("DELETE FROM auditoria_checklist_criterios WHERE id = $id");

echo "OK";
