<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// Verifica login
if (!isset($_SESSION['cpf'])) {
    header("Location: /login.php");
    exit;
}

$cpfLogado = preg_replace('/\D/', '', $_SESSION['cpf']);

/* ============================
   VERIFICA PARÂMETROS
============================ */
if (!isset($_GET['id']) || !isset($_GET['filial'])) {
    $_SESSION['flash'] = [
        'mensagem' => 'Parâmetros inválidos.',
        'tipo' => 'erro'
    ];
    header("Location: controlados.php");
    exit;
}

$id     = intval($_GET['id']);
$filial = intval($_GET['filial']);

/* ============================
   BUSCA O REGISTRO
============================ */
$stmt = $conn->prepare("SELECT * FROM controlados WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$registro = $stmt->get_result()->fetch_assoc();

if (!$registro) {
    $_SESSION['flash'] = [
        'mensagem' => 'Registro não encontrado.',
        'tipo' => 'erro'
    ];
    header("Location: controlados.php?filial=$filial");
    exit;
}

/* ============================
   BLOQUEIO DE EXCLUSÃO (LÓGICA CORRETA)
============================ */
$registradoBruto = trim($registro['registrado_por']);   // CPF ou primeiro nome
$registradoCPF   = preg_replace('/\D/', '', $registradoBruto);

$nomeLogado         = trim($_SESSION['usuario']);
$primeiroNomeLogado = explode(' ', $nomeLogado)[0];

$autorizado = false;

// Caso novo: registrado_por é CPF
if ($registradoCPF !== '' && $registradoCPF === $cpfLogado) {
    $autorizado = true;
}
// Caso antigo: registrado_por é primeiro nome
elseif ($registradoCPF === '' && strcasecmp($registradoBruto, $primeiroNomeLogado) === 0) {
    $autorizado = true;
}

if (!$autorizado) {
    $_SESSION['flash'] = [
        'mensagem' => 'Somente o criador do protocolo pode excluir.',
        'tipo' => 'erro'
    ];
    header("Location: controlados.php?filial=$filial");
    exit;
}

/* ============================
   EXCLUIR REGISTRO
============================ */
$stmt = $conn->prepare("DELETE FROM controlados WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$_SESSION['flash'] = [
    'mensagem' => 'Registro excluído com sucesso!',
    'tipo' => 'sucesso'
];

header("Location: controlados_registros.php?filial=$filial");
exit;
