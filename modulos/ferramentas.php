<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// Verifica login
if (!isset($_SESSION['cpf'])) {
    header("Location: /login.php");
    exit;
}

// Dados do usuário
$usuario = $_SESSION['usuario'] ?? 'Usuário';
$cpf     = $_SESSION['cpf'] ?? '';
$cargo   = strtolower($_SESSION['cargo'] ?? '');

// SUPER / CEO têm acesso total
$acessoTotal = in_array($cargo, ['super', 'ceo']);

// Contador do inventário
$totalItensInventario = contarItensInventario($conn);

// ===============================
// CONTEÚDO PRINCIPAL
// ===============================
ob_start();
?>

<h1 class="mb-3">🧰 Módulo de Ferramentas</h1>
<p>Olá, <strong><?= htmlspecialchars($usuario) ?></strong>. Selecione a ferramenta desejada:</p>

<div class="cards-grid">

    <?php if ($acessoTotal || temAcesso($conn, $cpf, "ferramentas_avaliacoes")): ?>
        <a href="avaliacoes_loja.php" class="card-global">
            <div class="card-global-icon">🏪</div>
            <h3 class="card-global-title">Avaliação de Loja</h3>
            <p class="card-global-text">Avaliação dos setores, limpeza, organização e indicadores.</p>
        </a>
    <?php endif; ?>

    <?php if ($acessoTotal || temAcesso($conn, $cpf, "ferramentas_auditoria_pp")): ?>
        <a href="auditoria_pp.php" class="card-global">
            <div class="card-global-icon">🛡️</div>
            <h3 class="card-global-title">Auditoria Prevenção e Perdas</h3>
            <p class="card-global-text">Auditoria completa de funcionamento, estrutura e segurança.</p>
        </a>
    <?php endif; ?>

    <?php if ($acessoTotal || temAcesso($conn, $cpf, "ferramentas_auditoria_checklist")): ?>
        <a href="auditoria_checklist.php" class="card-global">
            <div class="card-global-icon">📋</div>
            <h3 class="card-global-title">Auditoria Checklist</h3>
            <p class="card-global-text">Avaliação completa baseada em checklist operacional.</p>
        </a>
    <?php endif; ?>

    <?php if ($acessoTotal || temAcesso($conn, $cpf, "ferramentas_inventario")): ?>
        <a href="inventario.php" class="card-global">
            <div class="card-global-icon">📦</div>
            <h3 class="card-global-title">Inventário</h3>
            <p class="card-global-text">Gestão de equipamentos, itens e ativos por loja.</p>
            <p class="card-global-text" style="font-weight:bold; color:#34495e;">
                Itens registrados: <?= $totalItensInventario ?>
            </p>
        </a>
    <?php endif; ?>

    <?php if ($acessoTotal || temAcesso($conn, $cpf, "ferramentas_controlados")): ?>
        <a href="controlados.php" class="card-global">
            <div class="card-global-icon">💊</div>
            <h3 class="card-global-title">Controlados</h3>
            <p class="card-global-text">Registro e controle de medicamentos controlados.</p>
        </a>
    <?php endif; ?>

    <?php if ($acessoTotal || temAcesso($conn, $cpf, "ferramentas_controlados_farmaceutico")): ?>
        <a href="controlados_registros_farmaceutico.php" class="card-global">
            <div class="card-global-icon">💊</div>
            <h3 class="card-global-title">Controlados Farmacêutico</h3>
            <p class="card-global-text">Conferência de medicamentos controlados.</p>
        </a>
    <?php endif; ?>

</div>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
