<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';

$conn = conectar();

$idOriginal = intval($_POST['id_original'] ?? 0);
$titulo = trim($_POST['titulo'] ?? '');
$dataInicio = $_POST['data_inicio'] ?? null;
$dataFim = $_POST['data_fim'] ?? null;

if ($idOriginal <= 0 || !$titulo || !$dataInicio || !$dataFim) {
    setFlash('error', 'Dados inválidos.');
    header("Location: planos_acao_listar.php");
    exit;
}

// Buscar plano original
$sql = "SELECT * FROM planos_acao WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idOriginal);
$stmt->execute();
$plano = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$plano) {
    setFlash('error', 'Plano original não encontrado.');
    header("Location: planos_acao_listar.php");
    exit;
}

// Criar novo plano
$sqlN = "
    INSERT INTO planos_acao 
    (titulo, descricao, data_inicio, data_fim, status, criado_por, data_criacao)
    VALUES (?, ?, ?, ?, 'ativa', ?, NOW())
";
$stmtN = $conn->prepare($sqlN);

$criadoPor = intval($_SESSION['funcionario_id']);

$stmtN->bind_param(
    "ssssi",
    $titulo,
    $plano['descricao'],
    $dataInicio,
    $dataFim,
    $criadoPor
);

$stmtN->execute();
$novoId = $stmtN->insert_id;
$stmtN->close();

// Buscar tarefas originais
$sqlT = "SELECT * FROM tarefas_plano WHERE id_plano = ?";
$stmtT = $conn->prepare($sqlT);
$stmtT->bind_param("i", $idOriginal);
$stmtT->execute();
$tarefas = $stmtT->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtT->close();

// Clonar tarefas
$sqlC = "
    INSERT INTO tarefas_plano
    (id_plano, titulo, descricao, data_limite, responsavel_tipo, responsavel_id, status, data_criacao)
    VALUES (?, ?, ?, ?, ?, ?, 'pendente', NOW())
";
$stmtC = $conn->prepare($sqlC);

foreach ($tarefas as $t) {
    $novoTituloTarefa = $t['titulo'] . " - " . $titulo;

    $stmtC->bind_param(
        "issssi",
        $novoId,
        $novoTituloTarefa,
        $t['descricao'],
        $dataFim,
        $t['responsavel_tipo'],
        $t['responsavel_id']
    );

    $stmtC->execute();
}

setFlash('success', 'Plano clonado com sucesso!');
header("Location: planos_acao_listar.php");
exit;

