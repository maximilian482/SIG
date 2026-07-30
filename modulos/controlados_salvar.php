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

$cpfLogado      = trim(preg_replace('/\D/', '', $_SESSION['cpf']));
$registradoPor  = $cpfLogado;
$registradoNome = $_SESSION['usuario'];

/* ============================================================
   RECEBE DADOS DO FORMULÁRIO
============================================================ */
$filial        = $_POST['filial'] ?? '';
$data_venda    = $_POST['data_venda'] ?? '';
$codigo        = $_POST['codigo_produto'] ?? '';
$produto       = $_POST['produto'] ?? '';
$orcamento     = $_POST['orcamento'] ?? '';
$vendedor      = $_POST['vendedor'] ?? '';
$lote          = $_POST['lote'] ?? '';
$quantidade    = $_POST['quantidade'] ?? 0;
$observacao    = $_POST['observacao'] ?? '';

/* ============================================================
   VALIDAÇÃO
============================================================ */
if (
    empty($filial) || empty($data_venda) || empty($codigo) ||
    empty($produto) || empty($orcamento) || empty($vendedor) ||
    empty($lote) || empty($quantidade)
) {
    $_SESSION['flash'] = [
        'mensagem' => 'Preencha todos os campos obrigatórios.',
        'tipo' => 'error'
    ];
    header("Location: controlados_novo.php?filial={$filial}");
    exit;
}

/* ============================================================
   INSERT
============================================================ */
$stmt = $conn->prepare("
    INSERT INTO controlados 
    (filial_id, data_venda, codigo_produto, vendedor, produto, lote, orcamento, quantidade, registrado_por, registrado_nome, observacao, criado_em)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

$stmt->bind_param(
    "isssssissss",
    $filial,
    $data_venda,
    $codigo,
    $vendedor,
    $produto,
    $lote,
    $orcamento,
    $quantidade,
    $registradoPor,
    $registradoNome,
    $observacao
);

$stmt->execute();

/* ============================================================
   MENSAGEM PREMIUM + REDIRECIONAMENTO
============================================================ */
$_SESSION['flash'] = [
    'mensagem' => 'Registro criado com sucesso!',
    'tipo' => 'sucesso'
];

header("Location: controlados.php?filial={$filial}");
exit;
