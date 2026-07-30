<?php
session_start();

require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../dados/conexao.php';

$conn = conectar();

/*
    Função local para buscar nome do funcionário
*/
function buscarNomeLocal($conn, $cpf) {
    $stmt = $conn->prepare("SELECT nome FROM funcionarios WHERE cpf = ?");
    $stmt->bind_param("s", $cpf);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    return $r['nome'] ?? 'Não identificado';
}

$id = $_GET['id'] ?? 0;

if (!$id) {
    echo "<h2 style='color:red; text-align:center;'>❌ Evento não encontrado.</h2>";
    exit;
}

$stmt = $conn->prepare("
    SELECT *
    FROM cartoes_atribuicoes
    WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$dados = $stmt->get_result()->fetch_assoc();

if (!$dados) {
    echo "<h2 style='color:red; text-align:center;'>❌ Evento não encontrado.</h2>";
    exit;
}

$nomeFuncionario = buscarNomeLocal($conn, $dados['cpf_funcionario']);
$divergencia = $dados['saldo_entregue'] - $dados['saldo_devolvido'];

/*
    IDENTIFICAÇÃO DEFINITIVA DO GESTOR
*/

$nomeGestor = "Não identificado";

if (!empty($dados['cpf_gestor'])) {
    $nomeGestor = buscarNomeLocal($conn, $dados['cpf_gestor']);
}

ob_start();
?>

<link rel="stylesheet" href="/css/cartoes.css">

<style>
.assinatura-img {
    max-width: 280px;
    max-height: 120px;
    object-fit: contain;
    border: 1px solid #ccc;
    padding: 6px;
    background: #fff;
    margin-bottom: 10px;
}
.assinatura-bloco {
    margin-bottom: 25px;
}
.assinatura-bloco strong {
    font-size: 15px;
}
.timeline {
    list-style: none;
    padding-left: 0;
}
.timeline li {
    margin-bottom: 8px;
}
</style>

<h1 class="mb-3">🔍 Detalhes da Devolução</h1>

<a href="cartoes_historico.php?cartao=<?= $dados['codigo_cartao'] ?>" class="btn btn-cinza mb-3">⬅ Voltar</a>

<div class="cartoes-card">

    <h3>Informações Gerais</h3>

    <p><strong>Cartão:</strong> <?= $dados['codigo_cartao'] ?></p>
    <p><strong>Funcionário:</strong> <?= $nomeFuncionario ?></p>
    <p><strong>Gestor responsável:</strong> <?= $nomeGestor ?></p>
    <p><strong>Data de retirada:</strong> <?= date('d/m/Y H:i', strtotime($dados['data_atribuicao'])) ?></p>
    <p><strong>Data de devolução:</strong> <?= date('d/m/Y H:i', strtotime($dados['data_devolucao'])) ?></p>

    <?php if ($dados['id_ciclo']): ?>
        <p><strong>Ciclo:</strong> <?= $dados['id_ciclo'] ?></p>
    <?php else: ?>
        <p><strong>Ciclo:</strong> <span style="color:red;">Não vinculado</span></p>
    <?php endif; ?>

    <hr>

    <h3>Valores</h3>

    <p><strong>Saldo entregue:</strong> R$ <?= number_format($dados['saldo_entregue'], 2, ',', '.') ?></p>
    <p><strong>Saldo devolvido:</strong> R$ <?= number_format($dados['saldo_devolvido'], 2, ',', '.') ?></p>

    <p><strong>Divergência:</strong>
        <?php if ($divergencia != 0): ?>
            <span style="color:red; font-weight:bold;">
                ⚠ R$ <?= number_format($divergencia, 2, ',', '.') ?>
            </span>
        <?php else: ?>
            <span style="color:green; font-weight:bold;">
                ✔ Sem divergência
            </span>
        <?php endif; ?>
    </p>

    <hr>

    <h3>Assinaturas</h3>

    <div class="assinatura-bloco">
        <strong>👤 Funcionário (retirada): <?= $nomeFuncionario ?></strong><br>
        <?php if ($dados['assinatura_funcionario']): ?>
            <img src="<?= $dados['assinatura_funcionario'] ?>" class="assinatura-img">
        <?php else: ?>
            <p class="text-muted">Não registrada</p>
        <?php endif; ?>
    </div>

    <div class="assinatura-bloco">
        <strong>🧑‍💼 Gestor (retirada): <?= $nomeGestor ?></strong><br>
        <?php if ($dados['assinatura_gestor']): ?>
            <img src="<?= $dados['assinatura_gestor'] ?>" class="assinatura-img">
        <?php else: ?>
            <p class="text-muted">Não registrada</p>
        <?php endif; ?>
    </div>

    <div class="assinatura-bloco">
        <strong>👤 Funcionário (devolução): <?= $nomeFuncionario ?></strong><br>
        <?php if ($dados['assinatura_funcionario_devolucao']): ?>
            <img src="<?= $dados['assinatura_funcionario_devolucao'] ?>" class="assinatura-img">
        <?php else: ?>
            <p class="text-muted">Não registrada</p>
        <?php endif; ?>
    </div>

    <div class="assinatura-bloco">
        <strong>🧑‍💼 Gestor (devolução): <?= $nomeGestor ?></strong><br>
        <?php if ($dados['assinatura_gestor_devolucao']): ?>
            <img src="<?= $dados['assinatura_gestor_devolucao'] ?>" class="assinatura-img">
        <?php else: ?>
            <p class="text-muted">Não registrada</p>
        <?php endif; ?>
    </div>

    <hr>

    <h3>Timeline da Movimentação</h3>

    <ul class="timeline">
        <li><strong>Retirada:</strong> <?= date('d/m/Y H:i', strtotime($dados['data_atribuicao'])) ?></li>
        <li><strong>Devolução:</strong> <?= date('d/m/Y H:i', strtotime($dados['data_devolucao'])) ?></li>
        <?php if ($divergencia != 0): ?>
            <li style="color:red;"><strong>Divergência detectada:</strong> R$ <?= number_format($divergencia, 2, ',', '.') ?></li>
        <?php else: ?>
            <li style="color:green;"><strong>Sem divergência</strong></li>
        <?php endif; ?>
    </ul>

</div>

<?php
$conteudo = ob_get_clean();
include __DIR__ . '/../../includes/layout.php';
?>
