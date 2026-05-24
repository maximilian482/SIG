<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

if (!isset($_GET['id']) || !isset($_GET['filial'])) {
    header("Location: controlados_registros.php");
    exit;
}

$id     = intval($_GET['id']);
$filial = intval($_GET['filial']);
$cpfLogado = preg_replace('/\D/', '', $_SESSION['cpf']);

$stmt = $conn->prepare("SELECT registrado_por FROM controlados WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$registro = $stmt->get_result()->fetch_assoc();

if (!$registro) {
    $_SESSION['flash'] = [
        'mensagem' => 'Registro não encontrado.',
        'tipo' => 'error'
    ];
    header("Location: controlados_registros.php?filial=$filial");
    exit;
}

$registradoPor = preg_replace('/\D/', '', $registro['registrado_por']);
if ($registradoPor !== $cpfLogado) {
    $_SESSION['flash'] = [
        'mensagem' => 'Somente o criador do protocolo pode excluir.',
        'tipo' => 'error'
    ];
    header("Location: controlados_registros.php?filial=$filial");
    exit;
}

$stmt = $conn->prepare("DELETE FROM controlados WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$_SESSION['flash'] = [
    'mensagem' => 'Registro excluído com sucesso!',
    'tipo' => 'success'
];

header("Location: controlados_registros.php?filial=$filial");
exit;
