<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

include '../includes/menu.php';
include '../includes/head.php';
include '../perfil/menu_perfil.php';

$usuario = $_SESSION['usuario'] ?? 'Usuário';
$cpf     = $_SESSION['cpf'] ?? '';
$cargo   = strtolower($_SESSION['cargo'] ?? '');
$acessoTotal = in_array($cargo, ['super', 'ceo']);
$totalFuncionarios    = contarFuncionarios($conn);
$totalItensInventario = contarItensInventario($conn);
$totalLojas           = contarLojas($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestão</title>
</head>
<body>


<main class="layout-principal">
  <h1>📂 Painel de Gestão</h1>
  <p>Olá, <strong><?= htmlspecialchars($usuario) ?></strong>. Aqui estão os módulos administrativos disponíveis:</p>

  <div class="menu">
      <?php if ($cargo === 'gerente'): ?>
      <div class="card">
        <h2>🏪 Loja</h2>
        <p>Visualize os dados da sua unidade</p>
        <a href="painel_loja_gerente.php">Acessar</a>
      </div>
    <?php endif; ?>

    <?php if ($acessoTotal || temAcesso($conn, $cpf, 'relatorios')): ?>
      <div class="card">
        <h2>📄 Relatórios</h2>
        <p>Visualização de dados e exportações</p>
        <p style="font-weight:bold; color:#34495e;">📊 Acesso liberado</p>
        <a href="exportacao/index.php">Acessar</a>
      </div>
    <?php endif; ?>

    <?php if ($acessoTotal || temAcesso($conn, $cpf, 'cadastro_funcionarios')): ?>
      <div class="card">
        <h2>👥 Funcionários</h2>
        <p>Cadastro, edição e controle de acesso</p>
        <p style="font-weight:bold; color:#34495e;">👤 Total cadastrados: <?= $totalFuncionarios ?></p>
        <a href="funcionarios.php">Acessar</a>
      </div>
    <?php endif; ?>

    <?php if ($acessoTotal || temAcesso($conn, $cpf, 'inventario')): ?>
      <div class="card">
        <h2>📦 Inventário</h2>
        <p>Gestão de equipamentos por loja</p>
        <p style="font-weight:bold; color:#34495e;">📦 Itens registrados: <?= $totalItensInventario ?></p>
        <a href="inventario.php">Acessar</a>
      </div>
    <?php endif; ?>

    <?php if ($acessoTotal || temAcesso($conn, $cpf, 'lojas')): ?>
      <div class="card">
        <h2>🏬 Lojas</h2>
        <p>Visualize dados completos por unidade</p>
        <p style="font-weight:bold; color:#34495e;">🏢 Total de lojas: <?= $totalLojas ?></p>
        <a href="lojas.php">Acessar</a>
      </div>
    <?php endif; ?>

    <?php if ($acessoTotal || temAcesso($conn, $cpf, 'gerenciar_acessos')): ?>
      <div class="card">
        <h2>🔐 Gerenciar Acessos</h2>
        <p>Controle os módulos disponíveis para cada funcionário</p>
        <p style="font-weight:bold; color:#34495e;">🔒 Acesso administrativo</p>
        <a href="gerenciar_acessos.php">Acessar</a>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/../includes/scripts.php'; ?>
</body>
</html>
