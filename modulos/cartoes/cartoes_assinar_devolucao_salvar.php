<?php
session_start();

require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../dados/conexao.php';

$conn = conectar();

$cpf   = $_SESSION['cpf'] ?? '';
$cargo = strtolower(trim($_SESSION['cargo'] ?? ''));

$cargo = str_replace(['á','à','ã','â'], 'a', $cargo);
$cargo = str_replace(['é','ê'], 'e', $cargo);

if ($cargo === 'super' || $cargo === 'ceo') {
    $temAcesso = true;
} else {
    $temAcesso = temAcesso($conn, $cpf, 'cartoes');
}

if (!$temAcesso) {
    $_SESSION['flash'] = [
        'mensagem' => 'Você não possui acesso ao módulo de Cartões Corporativos.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_mestre.php");
    exit;
}

$id                     = $_POST['id'] ?? null;
$id_ciclo               = $_POST['id_ciclo'] ?? null;
$saldo_banco            = floatval($_POST['saldo_banco'] ?? 0);
$divergencia            = floatval($_POST['divergencia'] ?? 0);
$assinatura_funcionario = $_POST['assinatura_funcionario'] ?? null;
$assinatura_gestor      = $_POST['assinatura_gestor'] ?? null;
$cpf_gestor             = $_POST['cpf_gestor'] ?? null;

if (!$id || !$id_ciclo || !$saldo_banco || !$assinatura_funcionario || !$assinatura_gestor || !$cpf_gestor) {
    $_SESSION['flash'] = [
        'mensagem' => 'Erro ao receber dados da devolução.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_mestre.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM cartoes_atribuicoes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$atr = $stmt->get_result()->fetch_assoc();

if (!$atr) {
    $_SESSION['flash'] = [
        'mensagem' => 'Atribuição não encontrada.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_mestre.php");
    exit;
}

$codigo_cartao   = $atr['codigo_cartao'];
$cpf_funcionario = $atr['cpf_funcionario'];

// 1) Salvar assinaturas + saldo devolvido na atribuição
$stmt2 = $conn->prepare("
    UPDATE cartoes_atribuicoes
    SET assinatura_funcionario_devolucao = ?,
        assinatura_gestor_devolucao      = ?,
        cpf_gestor                       = ?,
        saldo_devolvido                  = ?,   -- saldo devolvido correto
        data_devolucao                   = NOW(),
        ativo = 0
    WHERE id = ?
");
$stmt2->bind_param(
    "sssdi",
    $assinatura_funcionario,
    $assinatura_gestor,
    $cpf_gestor,
    $saldo_banco,   // saldo devolvido
    $id
);
$stmt2->execute();

// 2) Fechar ciclo (divergência oficial)
$stmtCiclo = $conn->prepare("
    UPDATE cartoes_ciclos
    SET divergencia    = ?, 
        data_devolucao = NOW(),
        status         = 'DEVOLVIDO'
    WHERE id_ciclo = ?
");
$stmtCiclo->bind_param("di", $divergencia, $id_ciclo);
$stmtCiclo->execute();

// 3) Atualizar cartão → saldo atual + DISPONÍVEL
$stmt3 = $conn->prepare("
    UPDATE cartoes
    SET saldo_atual        = ?,
        status             = 'DISPONÍVEL',
        ultima_movimentacao = NOW()
    WHERE codigo_cartao = ?
");
$stmt3->bind_param("ds", $saldo_banco, $codigo_cartao);
$stmt3->execute();

// 4) Histórico (proteção contra duplicação)
$f = $conn->query("SELECT nome FROM funcionarios WHERE cpf = '$cpf_funcionario'")->fetch_assoc();
$nomeCompleto = trim($f['nome']);
$partes = explode(" ", $nomeCompleto);
$nomeReduzido = $partes[0] . " " . end($partes);

$check = $conn->prepare("
    SELECT id 
    FROM cartoes_historico
    WHERE id_atribuicao = ?
      AND acao = 'DEVOLVIDO'
    LIMIT 1
");
$check->bind_param("i", $id);
$check->execute();
$existe = $check->get_result()->fetch_assoc();

if (!$existe) {
    $stmt4 = $conn->prepare("
        INSERT INTO cartoes_historico
        (codigo_cartao, acao, descricao, id_atribuicao, data_hora, usuario)
        VALUES (?, 'DEVOLVIDO', CONCAT('Cartão devolvido por ', ?), ?, NOW(), ?)
    ");
    $stmt4->bind_param("ssis", $codigo_cartao, $nomeReduzido, $id, $cpf_gestor);
    $stmt4->execute();
}

$_SESSION['flash'] = [
    'mensagem' => 'Devolução registrada e ciclo fechado com sucesso!',
    'tipo' => 'sucesso'
];

if ($cargo === 'funcionario') {
    header("Location: cartoes_funcionario.php");
    exit;
}

header("Location: cartoes_ver_assinaturas_devolucao.php?id=$id");
exit;
?>
