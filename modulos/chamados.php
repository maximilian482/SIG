<?php
session_start();

require_once '../includes/funcoes.php';
$conn = conectar();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/bootstrap.php';

// ===============================
// VARIÁVEIS DO USUÁRIO
// ===============================
$usuario       = $_SESSION['usuario'];
$cpf           = $_SESSION['cpf'] ?? '';
$lojaUsuario   = intval($_SESSION['loja'] ?? 0);
$setorUsuario  = intval($_SESSION['setor'] ?? 0);
$nomeUsuario   = $_SESSION['nome'] ?? $usuario;
$cargo         = strtolower(trim($_SESSION['cargo'] ?? ''));
$usuarioId     = intval($_SESSION['funcionario_id'] ?? ($_SESSION['id_funcionario'] ?? 0));

// ===============================
// CONTEÚDO PRINCIPAL
// ===============================
ob_start();
?>
<link rel="stylesheet" href="/css/chamados.css">

<div class="pagina-chamados-menu">

    <h2 class="titulo-pagina">O que você deseja fazer?</h2>
    <p class="subtitulo-pagina">Escolha abaixo o tipo de atendimento que deseja abrir ou acompanhar.</p>

    <div class="grid-opcoes-chamados">

        <!-- SETORES -->
        <a href="chamados_setores_publico.php" class="card-opcao">
            <div class="icone-opcao">🏢</div>
            <h3>Chamados para Setores</h3>
            <p>Abrir solicitações internas para TI, RH, Financeiro, Compras e outros setores.</p>
        </a>

        <!-- LOJAS -->
        <a href="chamados_lojas_publico.php" class="card-opcao">
            <div class="icone-opcao">🏬</div>
            <h3>Chamados para Lojas</h3>
            <p>Enviar solicitações diretamente para qualquer filial da rede.</p>
        </a>

        <!-- TRILHO -->
        <a href="chamados_trilho.php" class="card-opcao destaque-trilho">
            <div class="icone-opcao">🚚</div>
            <h3>Trilho Logístico</h3>
            <p>Acompanhar, criar e gerenciar protocolos de transporte entre lojas.</p>
        </a>

    </div>

</div>

<?php
// ===============================
// RENDERIZAR LAYOUT PADRÃO
// ===============================
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
