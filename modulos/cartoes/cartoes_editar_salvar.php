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



// Recebe dados
$codigo_cartao = $_POST['codigo_cartao'] ?? null;

if (!$codigo_cartao) {
    $_SESSION['flash'] = [
        'mensagem' => 'Cartão inválido.',
        'tipo' => 'erro'
    ];
    header("Location: /modulos/cartoes/cartoes_mestre.php");
    exit;
}

// Busca cartão
$cartao = $conn->query("
    SELECT *
    FROM cartoes
    WHERE codigo_cartao = '$codigo_cartao'
")->fetch_assoc();

if (!$cartao) {
    $_SESSION['flash'] = [
        'mensagem' => 'Cartão não encontrado.',
        'tipo' => 'erro'
    ];
    header("Location: /modulos/cartoes/cartoes_mestre.php");
    exit;
}

// Busca atribuição ativa
$atr = $conn->query("
    SELECT a.*, f.nome
    FROM cartoes_atribuicoes a
    JOIN funcionarios f ON f.cpf = a.cpf_funcionario
    WHERE a.codigo_cartao = '$codigo_cartao'
      AND a.ativo = 1
")->fetch_assoc();

// Se cartão estiver AGUARDANDO ASSINATURA → bloqueia edição
if ($cartao['status'] === 'AGUARDANDO ASSINATURA') {

    $nome = $atr['nome'] ?? 'Funcionário';
    $p = explode(' ', trim($nome));
    $nomeReduzido = $p[0] . ' ' . end($p);

    $_SESSION['flash'] = [
        'mensagem' => "Este cartão está aguardando assinatura de <strong>$nomeReduzido</strong>. Exclua a atribuição antes de editar.",
        'tipo' => 'erro'
    ];

    header("Location: /modulos/cartoes/cartoes_editar.php?cartao=$codigo_cartao");
    exit;
}

// Dados do formulário
$banco             = $_POST['banco'] ?? $cartao['banco'];
$conta_associada   = $_POST['conta_associada'] ?? $cartao['conta_associada'];
$numero_cartao     = $_POST['numero_cartao'] ?? $cartao['numero_cartao'];
$limite            = $_POST['limite'] ?? $cartao['limite'];
$saldo_atual       = $_POST['saldo_atual'] ?? $cartao['saldo_atual'];
$vencimento_dia    = $_POST['vencimento_dia'] ?? $cartao['vencimento_dia']; // ✔ NOVO CAMPO
$status            = $_POST['status'] ?? $cartao['status'];
$motivo_inativacao = $_POST['motivo_inativacao'] ?? $cartao['motivo_inativacao'];

// Se estava EM USO e o novo status NÃO for EM USO → remover posse
if ($cartao['status'] === 'EM USO' && $status !== 'EM USO' && $atr) {

    // Encerra atribuição
    $conn->query("
        UPDATE cartoes_atribuicoes
        SET ativo = 0
        WHERE id = {$atr['id']}
    ");

    // Histórico com último usuário
    $nome = $atr['nome'];
    $p = explode(' ', trim($nome));
    $nomeReduzido = $p[0] . ' ' . end($p);

    $conn->query("
        INSERT INTO cartoes_historico
        (codigo_cartao, acao, descricao, data_hora)
        VALUES ('$codigo_cartao', 'DEVOLUÇÃO', 'Cartão devolvido. Último usuário: $nomeReduzido', NOW())
    ");
}

// Atualiza cartão
$stmt = $conn->prepare("
    UPDATE cartoes
    SET banco = ?,
        conta_associada = ?,
        numero_cartao = ?,
        limite = ?,
        saldo_atual = ?,
        vencimento_dia = ?,   -- ✔ NOVO CAMPO
        status = ?,
        motivo_inativacao = ?,
        ultima_movimentacao = NOW()
    WHERE codigo_cartao = ?
");

$stmt->bind_param(
    "sssddisss",
    $banco,
    $conta_associada,
    $numero_cartao,
    $limite,
    $saldo_atual,
    $vencimento_dia,   // ✔ NOVO CAMPO
    $status,
    $motivo_inativacao,
    $codigo_cartao
);

$stmt->execute();

// Mensagem premium
$_SESSION['flash'] = [
    'mensagem' => 'Cartão atualizado com sucesso!',
    'tipo' => 'sucesso'
];

header("Location: /modulos/cartoes/cartoes_mestre.php");
exit;
?>
