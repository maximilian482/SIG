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

// CSS extra (se quiser adicionar algo específico)
// $cssExtra = "/css/card.css";

// Conteúdo da página
$conteudo = '
<h1>📂 Painel de Gestão</h1>
<p>Olá, <strong>' . htmlspecialchars($usuario) . '</strong>. Aqui estão os módulos administrativos disponíveis:</p>

<div class="cards-container">

    ' . (
        ($acessoTotal || temAcesso($conn, $cpf, "gestao_relatorios")) ?
        '<div class="card">
            <h2>📄 Relatórios</h2>
            <p>Visualização de dados e exportações</p>
            <a href="exportacao/index.php">Acessar</a>
        </div>' : ''
    ) . '

    ' . (
        ($acessoTotal || temAcesso($conn, $cpf, "gestao_funcionarios")) ?
        '<div class="card">
            <h2>👥 Funcionários</h2>
            <p>Cadastro, edição e controle de acesso</p>
            <p style="font-weight:bold; color:#34495e;">Total cadastrados: ' . $totalFuncionarios . '</p>
            <a href="funcionarios_menu.php">Acessar</a>
        </div>' : ''
    ) . '


    ' . (
        ($acessoTotal || temAcesso($conn, $cpf, "gestao_lojas")) ?
        '<div class="card">
            <h2>🏬 Lojas</h2>
            <p>Visualize dados completos por unidade</p>
            <p style="font-weight:bold; color:#34495e;">Total de lojas: ' . $totalLojas . '</p>
            <a href="lojas.php">Acessar</a>
        </div>' : ''
    ) . '

    ' . (
        ($acessoTotal || temAcesso($conn, $cpf, "gestao_acessos")) ?
        '<div class="card">
            <h2>🔐 Gerenciar Acessos</h2>
            <p>Controle os módulos disponíveis para cada funcionário</p>
            <a href="gerenciar_acessos.php">Acessar</a>
        </div>' : ''
    ) . '

    ' . (
        ($acessoTotal || temAcesso($conn, $cpf, "gestao_painel_chamados")) ?
        '<div class="card">
            <h2>🛠️ Painel de Chamados</h2>
            <p>Gerenciamento geral dos chamados</p>
            <a href="chamados_admin.php">Acessar</a>
        </div>' : ''
    ) . '

</div>
';

// Inclui o layout final
include '../includes/layout.php';
