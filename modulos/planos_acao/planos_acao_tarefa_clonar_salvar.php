<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';

$conn = conectar();

$id_plano = intval($_POST['id_plano'] ?? 0);
$titulo = trim($_POST['titulo'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$responsavel_tipo = trim($_POST['responsavel_tipo'] ?? '');
$responsavel_id = trim($_POST['responsavel_id'] ?? '');
$data_limite = $_POST['data_limite'] ?: null;

if ($id_plano <= 0 || !$titulo || !$descricao) {
    $_SESSION['flash'] = ['tipo' => 'error', 'mensagem' => 'Preencha todos os campos obrigatórios.'];
    header("Location: planos_acao_detalhes.php?id=$id_plano");
    exit;
}

$sql = "
    INSERT INTO tarefas_plano 
    (id_plano, titulo, descricao, responsavel_tipo, responsavel_id, data_limite, criado_em, status)
    VALUES (?, ?, ?, ?, ?, ?, NOW(), 'pendente')
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isssis", $id_plano, $titulo, $descricao, $responsavel_tipo, $responsavel_id, $data_limite);
$stmt->execute();
$stmt->close();

$_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Tarefa clonada com sucesso!'];

header("Location: planos_acao_detalhes.php?id=$id_plano");
exit;
