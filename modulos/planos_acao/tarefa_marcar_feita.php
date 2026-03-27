<?php
require_once __DIR__ . '/../../config/bootstrap.php';
session_start();
require_once ROOT_PATH . '/includes/funcoes.php';

$conn = conectar();

$id_tarefa = isset($_POST['id_tarefa']) ? (int)$_POST['id_tarefa'] : 0;
$resposta  = trim($_POST['resposta'] ?? '');
$usuarioId = intval(
    $_SESSION['id_funcionario']
    ?? $_SESSION['funcionario_id']
    ?? 0
);

if ($id_tarefa <= 0 || $usuarioId <= 0) {
    http_response_code(400);
    echo 'Dados inválidos.';
    exit;
}

if (mb_strlen($resposta) < 5) {
    $_SESSION['flash'] = [
        'mensagem' => 'A resposta é muito curta. Descreva melhor o que foi feito (mínimo 5 caracteres).',
        'tipo'     => 'error'
    ];
    http_response_code(400);
    echo 'Resposta muito curta.';
    exit;
}

// registra interação
registrarInteracaoTarefa($conn, $id_tarefa, $usuarioId, 'resposta', $resposta);

// atualiza status da tarefa para aguardando avaliação
$sql = "UPDATE tarefas_plano SET status = 'aguardando_avaliacao' WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_tarefa);
$stmt->execute();
$stmt->close();

// mensagem premium
$_SESSION['flash'] = [
    'mensagem' => 'Tarefa concluída com sucesso! Agora aguarda avaliação.',
    'tipo'     => 'success'
];

// se for AJAX, só devolve OK
if (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    echo 'OK';
    exit;
}

// fallback: redireciona
header('Location: /modulos/planos_acao/minhas_tarefas.php');
exit;
