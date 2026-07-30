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
// ID da atribuição
$id = $_GET['id'] ?? null;
$origem = $_GET['origem'] ?? ''; // <-- origem inteligente

if (!$id) {
    $_SESSION['flash'] = [
        'mensagem' => 'Atribuição inválida.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_atribuir.php");
    exit;
}

// Busca dados da atribuição
$stmt = $conn->prepare("
    SELECT a.*, 
           f.nome AS funcionario_nome, 
           f.id_setor,
           c.banco,
           c.codigo_cartao
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
        'mensagem' => 'Atribuição não encontrada.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_atribuir.php");
    exit;
}

// Busca setor
$setorNome = '';
$set = $conn->prepare("SELECT nome FROM setores WHERE id = ?");
$set->bind_param("i", $atr['id_setor']);
$set->execute();
$setorNome = $set->get_result()->fetch_assoc()['nome'] ?? '—';

// Nome reduzido do funcionário
$nomeCompleto = trim($atr['funcionario_nome']);
$partes = explode(" ", $nomeCompleto);
$nomeReduzido = $partes[0] . " " . end($partes);

// ✔ Buscar nome do gestor usando CPF salvo
$nomeGestor = '—';
if (!empty($atr['cpf_gestor'])) {
    $stmtG = $conn->prepare("SELECT nome FROM funcionarios WHERE cpf = ?");
    $stmtG->bind_param("s", $atr['cpf_gestor']);
    $stmtG->execute();
    $nomeGestor = $stmtG->get_result()->fetch_assoc()['nome'] ?? '—';
}

// BOTÃO VOLTAR INTELIGENTE
if ($origem === 'historico') {
    $voltar = "cartoes_historico.php?cartao=" . $atr['codigo_cartao'];
} else {
    $voltar = "cartoes_atribuir.php";
}

ob_start();
?>

<div class="container py-4" style="max-width: 900px;">

    <h1 class="mb-3">🖊 Assinaturas da Atribuição</h1>
    
    <p class="text-muted">Visualize as assinaturas coletadas no momento da entrega do cartão.</p>

    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <h4 class="mb-3">📋 Informações da Atribuição</h4>

            <p><strong>Cartão:</strong> <?= $atr['codigo_cartao'] ?> — <?= $atr['banco'] ?></p>
            <p><strong>Funcionário:</strong> <?= $nomeReduzido ?></p>
            <p><strong>Setor:</strong> <?= $setorNome ?></p>
            <p><strong>Saldo Entregue:</strong> R$ <?= number_format($atr['saldo_entregue'], 2, ',', '.') ?></p>
            <p><strong>Data da Atribuição:</strong> <?= date('d/m/Y H:i', strtotime($atr['data_atribuicao'])) ?></p>

        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body text-center">
            <h4><?= $nomeReduzido ?></h4>
            <img src="<?= $atr['assinatura_funcionario'] ?>" style="max-width:100%; border:1px solid #ccc;">
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body text-center">
            <h4><?= $nomeGestor ?></h4> <!-- ✔ CORRIGIDO -->
            <img src="<?= $atr['assinatura_gestor'] ?>" style="max-width:100%; border:1px solid #ccc;">
        </div>
    </div>

<a href="<?= $voltar ?>" class="btn btn-secondary w-100">⬅ Voltar</a>

</div>

<?php
$conteudo = ob_get_clean();
include __DIR__ . '/../../includes/layout.php';
?>
