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

// Recebe ID
$id = $_GET['id'] ?? null;

if (!$id) {
    $_SESSION['flash'] = [
        'mensagem' => 'Atribuição inválida.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_atribuir.php");
    exit;
}

// Busca atribuição
$atr = $conn->query("SELECT * FROM cartoes_atribuicoes WHERE id = $id")->fetch_assoc();

if (!$atr) {
    $_SESSION['flash'] = [
        'mensagem' => 'Atribuição não encontrada.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_atribuir.php");
    exit;
}

// Se já tiver assinatura → NÃO pode excluir
if ($atr['assinatura_funcionario']) {
    $_SESSION['flash'] = [
        'mensagem' => 'Não é possível excluir uma atribuição já assinada.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_atribuir.php");
    exit;
}

$codigo_cartao = $atr['codigo_cartao'];

// Remove atribuição
$conn->query("DELETE FROM cartoes_atribuicoes WHERE id = $id");

// Remove histórico relacionado
$conn->query("DELETE FROM cartoes_historico WHERE codigo_cartao = '$codigo_cartao' AND acao = 'ATRIBUIÇÃO'");

// Volta cartão para disponível
$conn->query("
    UPDATE cartoes
    SET status = 'DISPONÍVEL'
    WHERE codigo_cartao = '$codigo_cartao'
");

// Mensagem
$_SESSION['flash'] = [
    'mensagem' => 'Atribuição excluída com sucesso!',
    'tipo' => 'sucesso'
];

header("Location: cartoes_atribuir.php");
exit;
?>
