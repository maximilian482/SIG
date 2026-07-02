<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

// Dados do usuário
$usuario = $_SESSION['usuario'] ?? 'Usuário';
$cpf     = $_SESSION['cpf'] ?? '';
$cargo   = strtolower($_SESSION['cargo'] ?? '');
$lojaId  = $_SESSION['loja'] ?? 0;

// SUPER / CEO têm acesso total
$acessoTotal = in_array($cargo, ['super', 'ceo']);

// Contadores gerais
$totalFuncionarios    = contarFuncionarios($conn);
$totalItensInventario = contarItensInventario($conn);
$totalLojas           = contarLojas($conn);

// Título da página
$titulo = "Painel de Gestão";

// ===============================
// CONTEÚDO PRINCIPAL
// ===============================
ob_start();
?>

<h1 class="mb-3">📂 Painel de Gestão</h1>
<p>Olá, <strong><?= htmlspecialchars($usuario) ?></strong>. Aqui estão os módulos administrativos disponíveis:</p>

<div class="cards-grid">

    <?php if ($acessoTotal || temAcesso($conn, $cpf, "gestao_relatorios")): ?>
        <a href="exportacao/index.php" class="card-global">
            <div class="card-global-icon">📄</div>
            <h3 class="card-global-title">Relatórios</h3>
            <p class="card-global-text">Visualização de dados e exportações.</p>
        </a>
    <?php endif; ?>

    <?php if ($acessoTotal || temAcesso($conn, $cpf, "gestao_funcionarios")): ?>
        <a href="funcionarios_menu.php" class="card-global">
            <div class="card-global-icon">👥</div>
            <h3 class="card-global-title">Funcionários</h3>
            <p class="card-global-text">Cadastro, edição e controle de acesso.</p>
            <p class="card-global-text" style="font-weight:bold; color:#34495e;">
                Total cadastrados: <?= $totalFuncionarios ?>
            </p>
        </a>
    <?php endif; ?>

    <?php if ($acessoTotal || temAcesso($conn, $cpf, "gestao_lojas")): ?>
        <a href="lojas.php" class="card-global">
            <div class="card-global-icon">🏬</div>
            <h3 class="card-global-title">Lojas</h3>
            <p class="card-global-text">Visualize dados completos por unidade.</p>
            <p class="card-global-text" style="font-weight:bold; color:#34495e;">
                Total de lojas: <?= $totalLojas ?>
            </p>
        </a>
    <?php endif; ?>

    <?php if ($acessoTotal || temAcesso($conn, $cpf, "gestao_acessos")): ?>
        <a href="gerenciar_acessos.php" class="card-global">
            <div class="card-global-icon">🔐</div>
            <h3 class="card-global-title">Gerenciar Acessos</h3>
            <p class="card-global-text">Controle os módulos disponíveis para cada funcionário.</p>
        </a>
    <?php endif; ?>

    <?php if ($acessoTotal || temAcesso($conn, $cpf, "gestao_painel_chamados")): ?>
        <a href="chamados_admin.php" class="card-global">
            <div class="card-global-icon">🛠️</div>
            <h3 class="card-global-title">Painel de Chamados</h3>
            <p class="card-global-text">Gerenciamento geral dos chamados.</p>
        </a>
    <?php endif; ?>

    <?php if ($acessoTotal || temAcesso($conn, $cpf, "gestao_compras_externas")): ?>
        <a href="compras_externas_gestao.php" class="card-global">
            <div class="card-global-icon">🛒</div>
            <h3 class="card-global-title">Compras Externas</h3>
            <p class="card-global-text">Gestão completa das solicitações de compras.</p>
        </a>
    <?php endif; ?>

</div>

<?php
$conteudo = ob_get_clean();
include '../includes/layout.php';
?>
