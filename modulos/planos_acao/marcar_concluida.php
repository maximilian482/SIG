<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';
$conn = conectar();

$tarefaId = intval($_POST['tarefa_id'] ?? 0);
$comentario = trim($_POST['comentario'] ?? '');
$usuario = intval($_SESSION['funcionario_id'] ?? 0);

header('Content-Type: application/json; charset=utf-8');

if (!$tarefaId || !$usuario) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Parâmetros inválidos']);
    exit;
}

// verificar que o usuário é o responsavel ou tem permissão
$stmtChk = $conn->prepare("SELECT responsavel_id, criado_por, status FROM tarefas_plano WHERE id = ? LIMIT 1");
$stmtChk->bind_param('i', $tarefaId);
$stmtChk->execute();
$row = $stmtChk->get_result()->fetch_assoc();
$stmtChk->close();

if (!$row) { echo json_encode(['success'=>false,'error'=>'Tarefa não encontrada']); exit; }
if (intval($row['responsavel_id']) !== $usuario) {
    echo json_encode(['success'=>false,'error'=>'Você não é o responsável por esta tarefa']); exit;
}

// atualizar status para aguardando_avaliacao e data_conclusao
$stmtUpd = $conn->prepare("UPDATE tarefas_plano SET status = 'aguardando_avaliacao', data_conclusao = NOW() WHERE id = ?");
$stmtUpd->bind_param('i', $tarefaId);
$ok = $stmtUpd->execute();
$stmtUpd->close();

if ($ok) {
    $stmtHist = $conn->prepare("INSERT INTO tarefas_historico (tarefa_id, acao, comentario, usuario_id) VALUES (?, 'concluida', ?, ?)");
    $stmtHist->bind_param('isi', $tarefaId, $comentario, $usuario);
    $stmtHist->execute();
    $stmtHist->close();

    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'error'=>'Falha ao atualizar tarefa']);
}
