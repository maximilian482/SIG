<?php
session_start();

require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../dados/conexao.php';

$conn = conectar();

// CPF sempre limpo e padronizado
$cpfLogado = trim(preg_replace('/\D/', '', $_SESSION['cpf'] ?? ''));

// Verifica acesso pelo EDITAR ACESSOS
if (!temAcesso($conn, $cpfLogado, 'cartoes')) {
    $_SESSION['flash'] = [
        'mensagem' => 'Você não possui acesso ao módulo de Cartões Corporativos.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_mestre.php");
    exit;
}

// Verifica método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash'] = [
        'mensagem' => 'Método inválido.',
        'tipo' => 'erro'
    ];
    header("Location: /modulos/cartoes/cartoes_ocorrencias.php");
    exit;
}

// Coleta dados
$data_ocorrencia = $_POST['data_ocorrencia'] ?? '';
$tipo            = $_POST['tipo'] ?? '';
$utilizador      = $_POST['utilizador'] ?? '';
$codigo_cartao   = $_POST['codigo_cartao'] ?? '';
$saldo_atual     = $_POST['saldo_atual'] ?? 0;
$observacao      = $_POST['observacao'] ?? '';

// Validações básicas
if (!$data_ocorrencia || !$tipo || !$utilizador || !$codigo_cartao) {
    $_SESSION['flash'] = [
        'mensagem' => 'Preencha todos os campos obrigatórios.',
        'tipo' => 'erro'
    ];
    header("Location: /modulos/cartoes/cartoes_ocorrencias.php");
    exit;
}

// Salva ocorrência
$stmt = $conn->prepare("
    INSERT INTO cartoes_ocorrencias 
    (data_ocorrencia, tipo, utilizador, codigo_cartao, saldo_atual, observacao)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("ssssss", 
    $data_ocorrencia,
    $tipo,
    $utilizador,
    $codigo_cartao,
    $saldo_atual,
    $observacao
);
$stmt->execute();

// Histórico automático
$hist = $conn->prepare("
    INSERT INTO cartoes_historico (codigo_cartao, acao, descricao, data_hora)
    VALUES (?, 'OCORRÊNCIA', CONCAT('Ocorrência registrada: ', ?), NOW())
");
$hist->bind_param("ss", $codigo_cartao, $tipo);
$hist->execute();

$_SESSION['flash'] = [
    'mensagem' => 'Ocorrência registrada com sucesso!',
    'tipo' => 'sucesso'
];

header("Location: /modulos/cartoes/cartoes_ocorrencias.php");
exit;
