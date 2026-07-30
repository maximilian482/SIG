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

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['flash'] = [
        'mensagem' => 'Ocorrência inválida.',
        'tipo' => 'erro'
    ];
    header("Location: /modulos/cartoes/cartoes_ocorrencias.php");
    exit;
}

// Buscar cartão para registrar histórico
$busca = $conn->query("SELECT codigo_cartao FROM cartoes_ocorrencias WHERE id = $id");
$dados = $busca->fetch_assoc();
$codigo_cartao = $dados['codigo_cartao'] ?? null;

// Excluir ocorrência
$conn->query("DELETE FROM cartoes_ocorrencias WHERE id = $id");

// Registrar histórico
if ($codigo_cartao) {
    $hist = $conn->prepare("
        INSERT INTO cartoes_historico (codigo_cartao, acao, descricao, data_hora)
        VALUES (?, 'EXCLUSÃO DE OCORRÊNCIA', 'Ocorrência removida pelo gestor', NOW())
    ");
    $hist->bind_param("s", $codigo_cartao);
    $hist->execute();
}

$_SESSION['flash'] = [
    'mensagem' => 'Ocorrência excluída com sucesso!',
    'tipo' => 'sucesso'
];

header("Location: /modulos/cartoes/cartoes_ocorrencias.php");
exit;
