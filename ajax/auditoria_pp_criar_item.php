<?php
session_start();

require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

header("Content-Type: application/json; charset=utf-8");

// Verifica login
if (!isset($_SESSION['cpf'])) {
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}

$cpf = $_SESSION['cpf'];

if (!temAcesso($conn, $cpf, 'ferramentas_auditoria_pp')) {
    echo json_encode(['erro' => 'Sem permissão']);
    exit;
}

$pergunta = trim($_POST['pergunta'] ?? '');

if ($pergunta === '') {
    echo json_encode(['erro' => 'Pergunta inválida']);
    exit;
}

/*
---------------------------------------------------------
INSERIR NOVO ITEM NA TABELA BASE
---------------------------------------------------------
*/
$sql = "INSERT INTO auditoria_pp_config (pergunta) VALUES (?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $pergunta);

if (!$stmt->execute()) {
    echo json_encode(['erro' => 'Erro ao inserir item']);
    exit;
}

$id = $stmt->insert_id;

/*
---------------------------------------------------------
RETORNAR JSON PARA O JS
---------------------------------------------------------
*/
echo json_encode([
    'id'       => $id,
    'pergunta' => $pergunta
]);
exit;
