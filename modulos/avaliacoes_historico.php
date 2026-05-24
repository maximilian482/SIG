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

$cpf = $_SESSION['cpf'];

// Verifica permissão
if (!temAcesso($conn, $cpf, 'avaliacoes_loja')) {
    $conteudo = "<h2 style='color:red; text-align:center; margin-top:40px;'>❌ Você não tem permissão para acessar esta página.</h2>";
    include ROOT_PATH . '/includes/layout.php';
    exit;
}

ob_start();
?>

<link rel="stylesheet" href="/css/avaliacoes_loja.css">

<div class="container-avaliacao">
    <div class="avaliacao-wrapper">

        <h2 class="titulo-pagina" style="text-align:center; margin-top:20px;">
            📜 Histórico de Avaliações
        </h2>

        <div class="card-premium" style="margin-top:30px; text-align:center;">
            <h3 class="card-titulo">Em construção</h3>
            <p class="subtitulo-pagina" style="margin-top:10px;">
                Estamos preparando uma área completa para consultar todas as avaliações realizadas.
            </p>

            <p style="margin-top:15px; font-size:15px; color:#666;">
                Em breve você poderá visualizar, filtrar e exportar o histórico completo.
            </p>

            <a href="avaliacoes_loja.php" class="btn btn-azul" style="margin-top:25px;">
                ⬅ Voltar
            </a>
        </div>

    </div>
</div>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
