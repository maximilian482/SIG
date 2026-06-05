<?php
require_once '../dados/conexao.php';
$conn = conectar();

$lojaId = $_POST['loja_id'] ?? null;
$itens = $_POST['itens'] ?? [];

if (!$lojaId) {
    echo "Erro";
    exit;
}

// Limpar ativações anteriores
$sqlDel = "DELETE FROM auditoria_pp_config_ativos WHERE loja_id = ?";
$stmtDel = $conn->prepare($sqlDel);
$stmtDel->bind_param("i", $lojaId);
$stmtDel->execute();

// Inserir novas ativações
$sql = "INSERT INTO auditoria_pp_config_ativos (loja_id, item_id) VALUES (?, ?)";
$stmt = $conn->prepare($sql);

foreach ($itens as $id) {
    $stmt->bind_param("ii", $lojaId, $id);
    $stmt->execute();
}

echo "OK";
