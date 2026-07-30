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

// Dados recebidos
$id = $_POST['id'] ?? null;
$assinatura_funcionario = $_POST['assinatura_funcionario'] ?? null;
$assinatura_gestor      = $_POST['assinatura_gestor'] ?? null;
$cpf_gestor             = $_POST['cpf_gestor'] ?? null;   // ✔ ADICIONADO

if (!$id || !$assinatura_funcionario || !$assinatura_gestor || !$cpf_gestor) {
    $_SESSION['flash'] = [
        'mensagem' => 'Erro ao receber assinaturas.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_atribuir.php");
    exit;
}

// Busca dados da atribuição
$atr = $conn->query("SELECT * FROM cartoes_atribuicoes WHERE id = $id")->fetch_assoc();

if (!$atr) {
    $_SESSION['flash'] = [
        'mensagem' => 'Atribuição não encontrada.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_atribuir.php");
    exit;
}

$codigo_cartao = $atr['codigo_cartao'];
$cpf_funcionario = $atr['cpf_funcionario'];

// Salva assinaturas + CPF do gestor
$stmt = $conn->prepare("
    UPDATE cartoes_atribuicoes
    SET assinatura_funcionario = ?, 
        assinatura_gestor = ?,
        cpf_gestor = ?       -- ✔ ADICIONADO
    WHERE id = ?
");
$stmt->bind_param("sssi", 
    $assinatura_funcionario, 
    $assinatura_gestor,
    $cpf_gestor,            // ✔ ADICIONADO
    $id
);
$stmt->execute();

// Atualiza status do cartão → agora está oficialmente em uso
$conn->query("
    UPDATE cartoes
    SET status = 'EM USO',
        ultima_movimentacao = NOW()
    WHERE codigo_cartao = '$codigo_cartao'
");

// Nome reduzido do funcionário
$f = $conn->query("SELECT nome FROM funcionarios WHERE cpf = '$cpf_funcionario'")->fetch_assoc();
$nomeCompleto = trim($f['nome']);
$partes = explode(" ", $nomeCompleto);
$nomeReduzido = $partes[0] . " " . end($partes);

// Histórico → RETIRADA (agora com CPF do gestor)
$stmt2 = $conn->prepare("
    INSERT INTO cartoes_historico
    (codigo_cartao, acao, descricao, id_atribuicao, data_hora, usuario)
    VALUES (?, 'RETIRADA', CONCAT('Cartão retirado e assinado por ', ?), ?, NOW(), ?)
");
$stmt2->bind_param("ssis", $codigo_cartao, $nomeReduzido, $id, $cpf_gestor);   // ✔ ADICIONADO
$stmt2->execute();

// Feedback
$_SESSION['flash'] = [
    'mensagem' => 'Cartão atribuído com sucesso!',
    'tipo' => 'sucesso'
];

header("Location: cartoes_ver_assinaturas.php?id=$id");
exit;

?>
