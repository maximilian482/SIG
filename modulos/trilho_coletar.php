<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

// ===============================
// VERIFICA LOGIN
// ===============================
if (!isset($_SESSION['usuario'])) {
    echo "❌ Acesso negado.";
    exit;
}

$motoboyId = intval($_SESSION['funcionario_id'] ?? 0);
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo "❌ ID inválido.";
    exit;
}

// ===============================
// BUSCAR PROTOCOLO
// ===============================
$sql = "
    SELECT id, status
    FROM chamados_trilho
    WHERE id = {$id}
";

$dados = $conn->query($sql)->fetch_assoc();

if (!$dados) {
    echo "❌ Protocolo não encontrado.";
    exit;
}

if ($dados['status'] !== 'faturado') {
    echo "❌ Somente protocolos faturados podem ser coletados.";
    exit;
}

// ===============================
// REGISTRAR COLETA
// ===============================
$stmt = $conn->prepare("
    UPDATE chamados_trilho
    SET status = 'em_rota',
        motoboy_id = ?,
        data_coleta = NOW()
    WHERE id = ?
");

$stmt->bind_param("ii", $motoboyId, $id);
$stmt->execute();

echo "✔ Coleta registrada com sucesso!";
exit;
