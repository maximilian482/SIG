<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';

$conn = conectar();

// Aceita apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit("Método inválido");
}

// IDs recebidos
$idTarefa = intval($_POST['id_tarefa'] ?? $_POST['tarefa_id'] ?? 0);
$planoId  = intval($_POST['id_plano'] ?? 0); // <-- SEMPRE definido aqui

$acao = $_POST['acao'] ?? '';
$comentario = trim($_POST['comentario'] ?? '');
$usuario = intval($_SESSION['funcionario_id'] ?? 0);

// Segurança
if ($idTarefa <= 0 || !$usuario) {
    exit("Dados inválidos");
}

/*
    Se id_plano não veio no POST, buscamos direto da tarefa.
    Isso resolve 100% dos casos.
*/
if ($planoId <= 0) {
    $sql = "SELECT id_plano FROM tarefas_plano WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idTarefa);
    $stmt->execute();
    $stmt->bind_result($planoId);
    $stmt->fetch();
    $stmt->close();
}

/* ============================
   AÇÕES DO GESTOR
   ============================ */

// APROVAR
if ($acao === 'aprovar') {
    $sql = "UPDATE tarefas_plano 
            SET status = 'concluida', avaliacao_comentario = ?, data_avaliacao = NOW()
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $comentario, $idTarefa);
    $stmt->execute();

    header("Location: planos_acao_detalhes.php?id=" . $planoId);
    exit;
}

// REABRIR
if ($acao === 'reabrir') {
    $sql = "UPDATE tarefas_plano 
            SET status = 'reaberta', avaliacao_comentario = ?, data_avaliacao = NOW()
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $comentario, $idTarefa);
    $stmt->execute();

    header("Location: planos_acao_detalhes.php?id=" . $planoId);
    exit;
}

// EXCLUIR
if ($acao === 'excluir') {
    $sql = "DELETE FROM tarefas_plano WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idTarefa);
    $stmt->execute();

    header("Location: planos_acao_detalhes.php?id=" . $planoId);
    exit;
}

// fallback
header("Location: planos_acao_detalhes.php?id=" . $planoId);
exit;
