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

// Dados enviados
$id               = $_POST['id'] ?? null;
$finalidade       = $_POST['finalidade'] ?? null;
$centro_custo     = $_POST['centro_custo'] ?? null;
$tipo_lancamento  = $_POST['tipo_lancamento'] ?? null;
$nota_fiscal      = $_POST['nota_fiscal'] ?? null;
$lancado_vetor    = $_POST['lancado_vetor'] ?? null;

// Validação básica
if (!$id) {
    $_SESSION['flash'] = [
        'mensagem' => 'Registro inválido.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_utilizacao.php");
    exit;
}

// Atualiza o registro
$stmt = $conn->prepare("
    UPDATE cartoes_gastos
    SET finalidade = ?,
        centro_custo = ?,
        tipo_lancamento = ?,
        nota_fiscal = ?,
        lancado_vetor = ?
    WHERE id = ?
");

$stmt->bind_param(
    "sssssi",
    $finalidade,
    $centro_custo,
    $tipo_lancamento,
    $nota_fiscal,
    $lancado_vetor,
    $id
);

$stmt->execute();

// Feedback
$_SESSION['flash'] = [
    'mensagem' => 'Informações atualizadas com sucesso!',
    'tipo' => 'sucesso'
];

header("Location: cartoes_gasto_detalhes.php?id=" . (int)$id);
exit;
