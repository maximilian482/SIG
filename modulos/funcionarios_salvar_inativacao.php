<?php
session_start();
require_once '../dados/conexao.php';

$conn = conectar();
if (!$conn) {
    $_SESSION['flash'] = [
        'mensagem' => '❌ Falha ao conectar ao banco de dados.',
        'tipo' => 'erro'
    ];
    header('Location: funcionarios.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: funcionarios.php');
    exit;
}

// Capturar dados
$id           = intval($_POST['id'] ?? 0);
$loja_id      = intval($_POST['loja'] ?? 0);
$desligamento = trim($_POST['desligamento'] ?? '');

$erros = [];

if ($id <= 0 || $loja_id <= 0 || $desligamento === '') {
    $_SESSION['flash'] = [
        'mensagem' => '❌ Dados incompletos para inativação.',
        'tipo' => 'erro'
    ];
    header('Location: funcionarios.php');
    exit;
}

// Atualizar campo de desligamento
$sql = "UPDATE funcionarios SET desligamento = ? WHERE id = ? AND loja_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $_SESSION['flash'] = [
        'mensagem' => '❌ Erro ao preparar atualização: ' . $conn->error,
        'tipo' => 'erro'
    ];
    header('Location: funcionarios.php');
    exit;
}

$stmt->bind_param('sii', $desligamento, $id, $loja_id);

if ($stmt->execute()) {

    $_SESSION['flash'] = [
        'mensagem' => '✔ Funcionário inativado com sucesso.',
        'tipo' => 'sucesso'
    ];

    $stmt->close();
    header('Location: funcionarios.php');
    exit;

} else {

    $_SESSION['flash'] = [
        'mensagem' => '❌ Erro ao inativar funcionário: ' . $stmt->error,
        'tipo' => 'erro'
    ];

    header('Location: funcionarios.php');
    exit;
}
?>
