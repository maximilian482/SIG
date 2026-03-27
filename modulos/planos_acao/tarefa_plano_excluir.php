<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';

$conn = conectar();

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['flash'] = [
        'mensagem' => 'Parâmetros inválidos.',
        'tipo' => 'error'
    ];
    header("Location: planos_acao_listar.php");
    exit;
}

/* ============================================================
   1) BUSCAR TAREFA
============================================================ */
$sqlT = "SELECT id, id_plano FROM tarefas_plano WHERE id = ? LIMIT 1";
$stmtT = $conn->prepare($sqlT);
$stmtT->bind_param("i", $id);
$stmtT->execute();
$tarefa = $stmtT->get_result()->fetch_assoc();
$stmtT->close();

if (!$tarefa) {
    $_SESSION['flash'] = [
        'mensagem' => 'Tarefa não encontrada.',
        'tipo' => 'error'
    ];
    header("Location: planos_acao_listar.php");
    exit;
}

$id_plano = intval($tarefa['id_plano']);

/* ============================================================
   2) BUSCAR PLANO
============================================================ */
$sqlPlano = "SELECT id, criado_por FROM planos_acao WHERE id = ? LIMIT 1";
$stmtPlano = $conn->prepare($sqlPlano);
$stmtPlano->bind_param("i", $id_plano);
$stmtPlano->execute();
$plano = $stmtPlano->get_result()->fetch_assoc();
$stmtPlano->close();

if (!$plano) {
    $_SESSION['flash'] = [
        'mensagem' => 'Plano não encontrado.',
        'tipo' => 'error'
    ];
    header("Location: planos_acao_listar.php");
    exit;
}

/* ============================================================
   3) PERMISSÃO
============================================================ */
$idUsuario = intval($_SESSION['id_funcionario'] ?? 0);
$isSuper = $_SESSION['is_super_ou_ceo'] ?? false;

if ($idUsuario <= 0 || (!$isSuper && $idUsuario !== intval($plano['criado_por']))) {
    $_SESSION['flash'] = [
        'mensagem' => 'Você não tem permissão para excluir esta tarefa.',
        'tipo' => 'error'
    ];
    header("Location: planos_acao_detalhes.php?id=" . $id_plano);
    exit;
}

/* ============================================================
   4) VERIFICAR SE EXISTEM INTERAÇÕES
============================================================ */
$sqlCheck = "SELECT COUNT(*) AS total FROM respostas_tarefas WHERE id_tarefa = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("i", $id);
$stmtCheck->execute();
$res = $stmtCheck->get_result()->fetch_assoc();
$stmtCheck->close();

$temInteracoes = $res['total'] > 0;

/* ============================================================
   5) SE TIVER INTERAÇÕES E AINDA NÃO CONFIRMOU, MOSTRAR ALERTA
============================================================ */
if ($temInteracoes && !isset($_GET['confirmar'])) {

    echo "
    <div style='
        max-width:600px;
        margin:60px auto;
        padding:25px;
        background:#fff3cd;
        border-left:6px solid #ff9800;
        border-radius:10px;
        font-family:Arial, sans-serif;
        box-shadow:0 0 12px rgba(0,0,0,0.1);
    '>
        <h2 style='color:#b36b00; margin-top:0;'>Atenção</h2>
        <p style='font-size:1.1rem;'>
            Esta tarefa possui <strong>interações registradas</strong> (comentários).<br>
            Deseja realmente excluir?
        </p>

        <div style='margin-top:20px;'>
            <a href='tarefa_plano_excluir.php?id={$id}&confirmar=1'
               style='padding:10px 18px; background:#d9534f; color:white; text-decoration:none; border-radius:6px; font-weight:bold; margin-right:10px;'>
               Sim, excluir definitivamente
            </a>

            <a href='planos_acao_detalhes.php?id={$id_plano}'
               style='padding:10px 18px; background:#6c757d; color:white; text-decoration:none; border-radius:6px; font-weight:bold;'>
               Cancelar
            </a>
        </div>
    </div>
    ";
    exit;
}

/* ============================================================
   6) EXCLUIR INTERAÇÕES (SE EXISTIREM)
============================================================ */
$sqlDelResp = "DELETE FROM respostas_tarefas WHERE id_tarefa = ?";
$stmtDelResp = $conn->prepare($sqlDelResp);
$stmtDelResp->bind_param("i", $id);
$stmtDelResp->execute();
$stmtDelResp->close();

/* ============================================================
   7) EXCLUIR A TAREFA
============================================================ */
$sql = "DELETE FROM tarefas_plano WHERE id = ? AND id_plano = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $_SESSION['flash'] = [
        'mensagem' => 'Erro interno ao preparar exclusão.',
        'tipo' => 'error'
    ];
    header("Location: planos_acao_detalhes.php?id=" . $id_plano);
    exit;
}

$stmt->bind_param("ii", $id, $id_plano);

if (!$stmt->execute()) {
    $_SESSION['flash'] = [
        'mensagem' => 'Erro ao excluir tarefa.',
        'tipo' => 'error'
    ];
} else {
    $_SESSION['flash'] = [
        'mensagem' => 'Tarefa excluída com sucesso.',
        'tipo' => 'success'
    ];
}

$stmt->close();

/* ============================================================
   8) REDIRECIONAR
============================================================ */
header("Location: planos_acao_detalhes.php?id=" . $id_plano);
exit;
