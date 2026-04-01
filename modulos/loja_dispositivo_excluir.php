<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

$id   = intval($_GET['id'] ?? 0);
$loja = intval($_GET['loja'] ?? 0);

if ($id <= 0 || $loja <= 0) {
    $_SESSION['flash'] = [
        'mensagem' => 'Operação inválida.',
        'tipo' => 'error'
    ];
    header("Location: loja.php?id=" . $loja . "&aba=dispositivos");
    exit;
}

$stmt = $conn->prepare("DELETE FROM lojas_dispositivos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$_SESSION['flash'] = [
    'mensagem' => 'Dispositivo excluído com sucesso!',
    'tipo' => 'success'
];

header("Location: loja.php?id=" . $loja . "&aba=dispositivos");
exit;