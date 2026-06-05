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

// Contador do inventário (mesma função do Gestão)
$totalItensInventario = contarItensInventario($conn);

ob_start();
?>

<link rel="stylesheet" href="/css/ferramentas.css">

<h1>🧰 Módulo de Ferramentas</h1>
<p>Olá, <strong><?= htmlspecialchars($usuario) ?></strong>. Selecione a ferramenta desejada:</p>

<div class="cards-container">

    <?php if ($acessoTotal || temAcesso($conn, $cpf, "ferramentas_avaliacoes")): ?>
    <div class="card">
        <h2>🏪 Avaliação de Loja</h2>
        <p>Avaliação dos setores, limpeza, organização e indicadores.</p>
        <a href="avaliacoes_loja.php">Acessar</a>
    </div>
    <?php endif; ?>

    <?php if ($acessoTotal || temAcesso($conn, $cpf, "ferramentas_auditoria_pp")): ?>
        <div class="card">
            <h2>🛡️ Auditoria Prevenção e Perdas</h2>
            <p>Auditoria completa de funcionamento, estrutura e segurança.</p>
            <a href="auditoria_pp.php">Acessar</a>
        </div>
    <?php endif; ?>


    <?php if ($acessoTotal || temAcesso($conn, $cpf, "ferramentas_inventario")): ?>
    <div class="card">
        <h2>📦 Inventário</h2>
        <p>Gestão de equipamentos, itens e ativos por loja.</p>
        <p style="font-weight:bold; color:#34495e;">Itens registrados: <?= $totalItensInventario ?></p>
        <a href="inventario.php">Acessar</a>
    </div>
    <?php endif; ?>

    <?php if ($acessoTotal || temAcesso($conn, $cpf, "ferramentas_controlados")): ?>
    <div class="card">
        <h2>💊 Controlados</h2>
        <p>Registro e controle de medicamentos controlados.</p>
        <a href="controlados.php">Acessar</a>
    </div>
    <?php endif; ?>

    <?php if ($acessoTotal || temAcesso($conn, $cpf, "ferramentas_controlados_farmaceutico")): ?>
    <div class="card">
        <h2>💊 Controlados Farmacêutico</h2>
        <p>Conferência de medicamentos controlados.</p>
        <a href="controlados_registros_farmaceutico.php">Acessar</a>
    </div>
    <?php endif; ?>


</div>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
