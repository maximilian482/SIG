<?php
session_start();
require_once '../includes/funcoes.php';
require_once __DIR__ . '/../config/bootstrap.php';

$conn = conectar();

// ===============================
// VERIFICA LOGIN
// ===============================
if (!isset($_SESSION['funcionario_id'])) {
    setFlash("error", "Sessão expirada. Faça login novamente.");
    header("Location: ../login.php");
    exit;
}

$usuarioLogado = intval($_SESSION['funcionario_id']);

// ===============================
// VALIDAR ID
// ===============================
$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    setFlash("error", "ID inválido.");
    header("Location: chamados_trilho.php?aba=aberto");
    exit;
}

// ===============================
// BUSCAR PROTOCOLO
// ===============================
$sql = "
    SELECT 
        id,
        solicitante_id,
        status,
        assinatura_path
    FROM chamados_trilho
    WHERE id = {$id}
";

$protocolo = $conn->query($sql)->fetch_assoc();

if (!$protocolo) {
    setFlash("error", "Protocolo não encontrado.");
    header("Location: chamados_trilho.php?aba=aberto");
    exit;
}

// ===============================
// PERMISSÃO: SÓ EXCLUI QUEM CRIOU
// ===============================
if ($protocolo['solicitante_id'] != $usuarioLogado) {
    setFlash("error", "Você não tem permissão para excluir este protocolo.");
    header("Location: chamados_trilho.php?aba=aberto");
    exit;
}

// ===============================
// BLOQUEAR EXCLUSÃO SE NÃO ESTIVER ABERTO
// ===============================
if ($protocolo['status'] !== 'aberto') {
    setFlash("error", "Somente protocolos em estado ABERTO podem ser excluídos.");
    header("Location: chamados_trilho.php?aba=aberto");
    exit;
}

// ===============================
// APAGAR ASSINATURA (SE EXISTIR)
// ===============================
if (!empty($protocolo['assinatura_path'])) {
    $arquivo = "../uploads/assinaturas/" . $protocolo['assinatura_path'];
    if (file_exists($arquivo)) {
        unlink($arquivo);
    }
}

// ===============================
// EXCLUIR ITENS DO PROTOCOLO
// ===============================
$stmtItens = $conn->prepare("DELETE FROM trilho_itens WHERE trilho_id = ?");
$stmtItens->bind_param("i", $id);
$stmtItens->execute();

// ===============================
// EXCLUIR O PROTOCOLO
// ===============================
$stmt = $conn->prepare("DELETE FROM chamados_trilho WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    setFlash("warning", "Protocolo excluído com sucesso!");
} else {
    setFlash("error", "Erro ao excluir protocolo.");
}

$stmt->close();

// ===============================
// REDIRECIONAR
// ===============================
header("Location: chamados_trilho.php?aba=aberto");
exit;
