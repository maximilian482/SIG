<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

if (!isset($_GET['id']) || !isset($_GET['filial'])) {
    header("Location: controlados.php");
    exit;
}

$id     = intval($_GET['id']);
$filial = intval($_GET['filial']);
$cpfLogado = preg_replace('/\D/', '', $_SESSION['cpf']);

/* ============================
   BUSCA O REGISTRO
============================ */
$stmt = $conn->prepare("SELECT registrado_por FROM controlados WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$registro = $stmt->get_result()->fetch_assoc();

if (!$registro) {
    $_SESSION['flash'] = [
        'mensagem' => 'Registro não encontrado.',
        'tipo' => 'error'
    ];
    header("Location: controlados.php?filial=$filial");
    exit;
}

/* ============================
   BLOQUEIO DE EXCLUSÃO
============================ */
if ($registro['registrado_por'] !== $cpfLogado) {
    $_SESSION['flash'] = [
        'mensagem' => 'Somente o criador do protocolo pode excluir.',
        'tipo' => 'error'
    ];
    header("Location: controlados.php?filial=$filial");
    exit;
}

/* ============================
   EXCLUI O REGISTRO
============================ */
$stmt = $conn->prepare("DELETE FROM controlados WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

/* ============================
   FLASH MESSAGE PREMIUM
============================ */
$_SESSION['flash'] = [
    'mensagem' => 'Registro excluído com sucesso!',
    'tipo' => 'success'
];

// REDIRECIONAMENTO

if (isset($_GET['origem']) && $_GET['origem'] === 'registros') {
    header("Location: controlados_registros.php?filial=$filial");
} else {
    header("Location: controlados.php?filial=$filial");
}

