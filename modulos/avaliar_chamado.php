<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

$id = intval($_POST['id'] ?? 0);
$avaliacao = $_POST['avaliacao'] ?? '';
$nota = intval($_POST['nota'] ?? 0);
$justificativa = trim($_POST['justificativa'] ?? '');
$idFuncionario = intval($_SESSION['id_funcionario'] ?? 0);
$dataAgora = date('Y-m-d H:i:s');

if ($id <= 0) {
    echo "ID inválido.";
    exit;
}

if ($avaliacao === "") {
    echo "Selecione se você foi atendido.";
    exit;
}

if ($avaliacao === "Sim" && ($nota < 1 || $nota > 5)) {
    echo "Selecione uma nota de 1 a 5.";
    exit;
}

if ($avaliacao === "Não" && $justificativa === "") {
    echo "Explique o motivo.";
    exit;
}

// ===============================
// AVALIAÇÃO POSITIVA → ENCERRAR
// ===============================
if ($avaliacao === "Sim") {

    $sql = $conn->prepare("
        UPDATE chamados
        SET avaliacao = 'Sim',
            nota_estrelas = ?,
            justificativa = NULL,
            status = 'encerrado',
            data_avaliacao = ?,
            usuario_avaliacao = ?
        WHERE id = ?
    ");
    $sql->bind_param("isii", $nota, $dataAgora, $idFuncionario, $id);
    $sql->execute();

    // Histórico
    $msg = "Chamado encerrado com avaliação positiva (nota: {$nota})";
    $hist = $conn->prepare("
        INSERT INTO respostas_chamados (chamado_id, resposta, tipo, respondido_por, data)
        VALUES (?, ?, 'sistema', ?, ?)
    ");
    $hist->bind_param("isis", $id, $msg, $idFuncionario, $dataAgora);
    $hist->execute();

    echo "Avaliação registrada com sucesso!";
    exit;
}

// ===============================
// AVALIAÇÃO NEGATIVA → REABRIR
// ===============================
if ($avaliacao === "Não") {

    $sql = $conn->prepare("
        UPDATE chamados
        SET avaliacao = 'Não',
            nota_estrelas = NULL,
            justificativa = ?,
            status = 'reaberto',
            data_reabertura = ?,
            usuario_avaliacao = ?
        WHERE id = ?
    ");
    $sql->bind_param("ssii", $justificativa, $dataAgora, $idFuncionario, $id);
    $sql->execute();

    // Histórico
    $msg = "Chamado reaberto após avaliação negativa: {$justificativa}";
    $hist = $conn->prepare("
        INSERT INTO respostas_chamados (chamado_id, resposta, tipo, respondido_por, data)
        VALUES (?, ?, 'sistema', ?, ?)
    ");
    $hist->bind_param("isis", $id, $msg, $idFuncionario, $dataAgora);
    $hist->execute();

    echo "Chamado reaberto com sucesso!";
    exit;
}

echo "Erro inesperado.";
exit;
