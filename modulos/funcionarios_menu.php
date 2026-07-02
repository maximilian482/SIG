<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
include ROOT_PATH . '/includes/funcoes.php';

// Título da página
$titulo = "Gestão de Funcionários";

// ===============================
// CONTEÚDO PRINCIPAL
// ===============================
ob_start();
?>

<h1 class="mb-3">👥 Gestão de Funcionários</h1>
<p>Escolha uma das opções abaixo:</p>

<div class="cards-grid">

    <a href="funcionarios.php" class="card-global">
        <div class="card-global-icon">👥</div>
        <h3 class="card-global-title">Funcionários</h3>
        <p class="card-global-text">Listar, adicionar e editar funcionários.</p>
    </a>

    <a href="funcionarios_gestao.php" class="card-global">
        <div class="card-global-icon">📊</div>
        <h3 class="card-global-title">Gestão de Funcionários</h3>
        <p class="card-global-text">Visão geral, exportações e ações em lote.</p>
    </a>

    <a href="funcionarios_gestao_cargos.php" class="card-global">
        <div class="card-global-icon">🧩</div>
        <h3 class="card-global-title">Gestão de Cargos</h3>
        <p class="card-global-text">Adicionar, editar e excluir cargos.</p>
    </a>

    <a href="funcionarios_gestao_setores.php" class="card-global">
        <div class="card-global-icon">🧭</div>
        <h3 class="card-global-title">Gestão de Setores</h3>
        <p class="card-global-text">Adicionar, editar e excluir setores.</p>
    </a>

</div>

<br>
<a class="btn btn-secondary" href="gestao.php">🔙 Voltar ao Painel de Gestão</a>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . "/includes/layout.php";
?>
