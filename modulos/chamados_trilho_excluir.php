<?php
session_start();
require_once '../includes/funcoes.php';
require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json');

$conn = conectar();

// ===============================
// VERIFICA LOGIN
// ===============================
if (!isset($_SESSION['funcionario_id'])) {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Sessão expirada. Faça login novamente."
    ]);
    exit;
}

$usuarioLogado = intval($_SESSION['funcionario_id']);

// ===============================
// VALIDAR ID
// ===============================
$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "ID inválido."
    ]);
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
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Protocolo não encontrado."
    ]);
    exit;
}

// ===============================
// PERMISSÃO: SÓ EXCLUI QUEM CRIOU
// ===============================
if ($protocolo['solicitante_id'] != $usuarioLogado) {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Você não tem permissão para excluir este protocolo."
    ]);
    exit;
}

// ===============================
// BLOQUEAR EXCLUSÃO SE NÃO ESTIVER ABERTO
// ===============================
if ($protocolo['tipo'] === 'medicamento' && $protocolo['status'] !== 'aberto') {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Somente protocolos em estado ABERTO podem ser excluídos."
    ]);
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
    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Protocolo excluído com sucesso!"
    ]);
} else {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Erro ao excluir protocolo."
    ]);
}

$stmt->close();
exit;
