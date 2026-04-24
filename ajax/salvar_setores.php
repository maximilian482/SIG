<?php
session_start();

require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// Verifica login
if (!isset($_SESSION['cpf'])) {
    exit('Acesso negado');
}

$lojaId = intval($_POST['loja_id'] ?? 0);
$setores = $_POST['setores'] ?? [];

if ($lojaId <= 0) {
    exit('Loja inválida');
}

// ===============================
// LIMPAR SETORES DA LOJA
// ===============================
$sqlDel = "DELETE FROM lojas_setores WHERE loja_id = ?";
$stmt = $conn->prepare($sqlDel);
$stmt->bind_param("i", $lojaId);
$stmt->execute();

// ===============================
// INSERIR NOVOS SETORES
// ===============================
$sqlIns = "INSERT INTO lojas_setores (loja_id, setor_id) VALUES (?, ?)";
$stmtIns = $conn->prepare($sqlIns);

foreach ($setores as $setorId) {
    $setorId = intval($setorId);
    $stmtIns->bind_param("ii", $lojaId, $setorId);
    $stmtIns->execute();
}

echo "Configurações salvas com sucesso!";
