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

$id = $_GET['id'] ?? null;
$origem = $_GET['origem'] ?? '';

if (!$id) {
    $_SESSION['flash'] = [
        'mensagem' => 'ID inválido.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_mestre.php");
    exit;
}

// Busca dados da devolução
$stmt = $conn->prepare("
    SELECT a.*, 
           f.nome AS funcionario_nome,
           c.codigo_cartao,
           c.banco
    FROM cartoes_atribuicoes a
    JOIN funcionarios f ON f.cpf = a.cpf_funcionario
    JOIN cartoes c ON c.codigo_cartao = a.codigo_cartao
    WHERE a.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$atr = $stmt->get_result()->fetch_assoc();

if (!$atr) {
    $_SESSION['flash'] = [
        'mensagem' => 'Registro não encontrado.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_mestre.php");
    exit;
}

// Nome reduzido
$nomeCompleto = trim($atr['funcionario_nome']);
$partes = explode(" ", $nomeCompleto);
$nomeReduzido = $partes[0] . " " . end($partes);

// Botão voltar inteligente
if ($origem === 'historico') {
    $voltar = "cartoes_historico.php?cartao=" . $atr['codigo_cartao'];
} else {
    $voltar = "cartoes_mestre.php";
}

ob_start();
?>

<div class="container py-4" style="max-width: 900px;">

    <h1 class="mb-3">🔄 Assinaturas da Devolução</h1>
    <p class="text-muted">Assinaturas coletadas no momento da devolução do cartão.</p>

    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <h4 class="mb-3">📋 Informações da Devolução</h4>

            <p><strong>Cartão:</strong> <?= $atr['codigo_cartao'] ?> — <?= $atr['banco'] ?></p>
            <p><strong>Funcionário:</strong> <?= $nomeReduzido ?></p>
            <p><strong>Saldo Devolvido:</strong> R$ <?= number_format($atr['saldo_devolvido'], 2, ',', '.') ?></p>
            <p><strong>Data da Devolução:</strong> <?= date('d/m/Y H:i') ?></p>

        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body text-center">
            <h4><?= $nomeReduzido ?></h4>
            <img src="<?= $atr['assinatura_funcionario_devolucao'] ?>" style="max-width:100%; border:1px solid #ccc;">
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body text-center">
            <h4><?= $_SESSION['nome'] ?></h4>
            <img src="<?= $atr['assinatura_gestor_devolucao'] ?>" style="max-width:100%; border:1px solid #ccc;">
        </div>
    </div>

    <a href="<?= $voltar ?>" class="btn btn-secondary w-100">⬅ Voltar</a>

</div>

<?php
$conteudo = ob_get_clean();
include __DIR__ . '/../../includes/layout.php';
?>
