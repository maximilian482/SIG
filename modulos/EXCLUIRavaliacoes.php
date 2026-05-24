<?php
session_start();

require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// Verifica login
if (!isset($_SESSION['cpf'])) {
    header("Location: /login.php");
    exit;
}


ob_start();
?>

<link rel="stylesheet" href="/css/avaliacoes.css">

<div class="container-menu-avaliacoes">

    <h2 class="titulo-menu">⭐ Módulo de Avaliações</h2>
    <p class="subtitulo-menu">Escolha o tipo de avaliação que deseja acessar.</p>

    <div class="cards-menu">

        <!-- Avaliação de Loja -->
        <a href="avaliacoes_loja.php" class="card-menu">
            <div class="card-icone">🏪</div>
            <h3>Avaliação de Loja</h3>
            <p>Avaliação dos setores, limpeza, organização, exposição e indicadores.</p>
        </a>

        <!-- Avaliação de Funcionários (futuro) -->
        <a href="#" class="card-menu desativado">
            <div class="card-icone">👥</div>
            <h3>Avaliação de Funcionários</h3>
            <p>Em breve: desempenho, atendimento e indicadores individuais.</p>
        </a>

        <!-- Avaliação de Motoboys (futuro) -->
        <a href="#" class="card-menu desativado">
            <div class="card-icone">🛵</div>
            <h3>Avaliação de Motoboys</h3>
            <p>Em breve: pontualidade, cuidado e qualidade da entrega.</p>
        </a>

        <!-- Configuração de setores por loja -->
        <a href="avaliacoes_setores.php" class="card-menu">
            <div class="card-icone">🧩</div>
            <h3>Configurar Setores</h3>
            <p>Defina quais setores cada loja possui.</p>
        </a>

        <!-- Dashboard -->
        <a href="dashboard_avaliacoes.php" class="card-menu">
            <div class="card-icone">📊</div>
            <h3>Dashboard</h3>
            <p>Indicadores, gráficos e evolução das avaliações.</p>
        </a>

    </div>

</div>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
