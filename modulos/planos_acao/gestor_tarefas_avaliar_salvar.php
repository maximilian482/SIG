<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';

$conn = conectar();

$id_tarefa  = intval($_POST['tarefa_id'] ?? 0);
$id_plano   = intval($_POST['id_plano'] ?? 0);
$acao       = trim($_POST['acao'] ?? '');
$comentario = trim($_POST['comentario'] ?? '');
$usuario_id = $_SESSION['usuario_id'] ?? null;

if ($id_tarefa <= 0 || $id_plano <= 0 || !$acao) {
    $_SESSION['flash'] = [
        'mensagem' => 'Dados inválidos.',
        'tipo'     => 'error'
    ];
    header("Location: planos_acao_detalhes.php?id={$id_plano}");
    exit;
}

// Validação extra no backend para reabertura
if ($acao === 'reabrir' && mb_strlen($comentario) < 5) {
    http_response_code(400);
    exit;
}

// Confere se a tarefa existe
$sql = "SELECT id_plano FROM tarefas_plano WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_tarefa);
$stmt->execute();
$tarefa = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tarefa) {
    $_SESSION['flash'] = [
        'mensagem' => 'Tarefa não encontrada.',
        'tipo'     => 'error'
    ];
    header("Location: planos_acao_detalhes.php?id={$id_plano}");
    exit;
}

switch ($acao) {
    case 'aprovar':
        $novoStatus = 'concluida';
        $msg = "Tarefa aprovada.";
        break;

    case 'reabrir':
        $novoStatus = 'reaberta';
        $msg = "Tarefa reaberta.";
        break;

    case 'excluir':
        $novoStatus = 'excluida';
        $msg = "Tarefa excluída.";
        break;

    default:
        $_SESSION['flash'] = [
            'mensagem' => 'Ação inválida.',
            'tipo'     => 'error'
        ];
        header("Location: planos_acao_detalhes.php?id={$id_plano}");
        exit;
}

// Atualiza status
$sqlU = "UPDATE tarefas_plano SET status = ?, atualizado_em = NOW() WHERE id = ?";
$stmtU = $conn->prepare($sqlU);
$stmtU->bind_param("si", $novoStatus, $id_tarefa);
$stmtU->execute();
$stmtU->close();

// Registra histórico SOMENTE se houver comentário real
if ($comentario !== '') {
    $sqlH = "INSERT INTO respostas_tarefas (id_tarefa, usuario_id, tipo, mensagem)
             VALUES (?, ?, 'gestor', ?)";
    $stmtH = $conn->prepare($sqlH);
    $stmtH->bind_param("iis", $id_tarefa, $usuario_id, $comentario);
    $stmtH->execute();
    $stmtH->close();
}

// Mensagem premium
$_SESSION['flash'] = [
    'mensagem' => $msg,
    'tipo'     => 'success'
];

header("Location: planos_acao_detalhes.php?id={$id_plano}");
exit;
