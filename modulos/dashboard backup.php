<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

include '../includes/menu.php';
include '../includes/head.php';
include '../perfil/menu_perfil.php';

$caminhoFoto = caminhoFotoPerfil($conn, $_SESSION['id_funcionario']);
$usuario     = $_SESSION['usuario'];
$cpf         = $_SESSION['cpf'];
$cargo       = strtolower($_SESSION['cargo'] ?? '');
$loja        = $_SESSION['loja'] ?? '';
$lojaId      = $_SESSION['loja'] ?? 0;
$acessoTotal = in_array($cargo, ['super', 'ceo']);

$totalFuncionarios     = contarFuncionarios($conn);
$totalItensInventario  = contarItensInventario($conn);
$totalLojas            = contarLojas($conn);
$chamados              = listarChamados($conn);

$pendenciasTI         = contarPendenciasPorSetor($chamados, 'TI');
$pendenciasManutencao = contarPendenciasPorSetor($chamados, 'Manutencao');
$pendenciasSupervisao = contarPendenciasPorSetor($chamados, 'Supervisao');
$pendenciasPainel     = contarChamadosLoja($chamados, null);
$chamadosLoja         = contarChamadosLoja($chamados, $loja);
$pendenciasInconformidades = contarInconformidades($conn);
$pendenciasLoja       = contarInconformidadesLoja($conn, $lojaId);
?>

<!DOCTYPE html>
<html lang="pt-br">
<main style="padding: 20px; max-width: 1200px; margin: auto;">
  <h1>🛍️ Sistema de Gestão de Lojas</h1>
  <p>Olá, <strong><?= htmlspecialchars($usuario) ?></strong>. Escolha uma área para gerenciar:</p>

  <div class="menu" style="display: flex; flex-wrap: wrap; gap: 20px;">

    <!-- Exemplo de card -->
    <!-- <div class="card">
      <h2>📋 Abrir Chamados</h2>
      <p>Acompanhe os chamados abertos pela sua loja</p>
      <p style="font-weight:bold; color:<?= $chamadosLoja > 0 ? '#c0392b' : '#2ecc71' ?>">
        <?= $chamadosLoja > 0 ? '⚠️' : '✅' ?> Pendências: <?= $chamadosLoja ?>
      </p>
      <a href="acompanhar_chamados_publico.php">Acessar</a>
    </div> -->

    <?php if (($cargo === 'supervisao' || temAcesso($conn, $cpf, 'chamados_supervisao'))): ?>
      <div class="card">
        <h2>🧭 Chamados Supervisão</h2>
        <p>Gerencie chamados relacionados à supervisão de loja</p>
        <p style="font-weight:bold; color:<?= $pendenciasSupervisao > 0 ? '#c0392b' : '#2ecc71' ?>">
          <?= $pendenciasSupervisao > 0 ? '⚠️' : '✅' ?> Pendências: <?= $pendenciasSupervisao ?>
        </p>
        <a href="chamados_supervisao.php">Acessar</a>
      </div>
    <?php endif; ?>

    <?php if (($cargo === 'ti' || temAcesso($conn, $cpf, 'chamados_ti'))): ?>
      <div class="card">
        <h2>🖥️ Chamados TI</h2>
        <p>Gerencie chamados técnicos</p>
        <p style="font-weight:bold; color:<?= $pendenciasTI > 0 ? '#c0392b' : '#2ecc71' ?>">
          <?= $pendenciasTI > 0 ? '⚠️' : '✅' ?> Pendências: <?= $pendenciasTI ?>
        </p>
        <a href="chamados_ti.php">Acessar</a>
      </div>
    <?php endif; ?>

    <?php if (($cargo === 'manutencao' || temAcesso($conn, $cpf, 'chamados_manutencao'))): ?>
      <div class="card">
        <h2>🔧 Chamados Manutenção</h2>
        <p>Gerencie chamados de infraestrutura</p>
        <p style="font-weight:bold; color:<?= $pendenciasManutencao > 0 ? '#c0392b' : '#2ecc71' ?>">
          <?= $pendenciasManutencao > 0 ? '⚠️' : '✅' ?> Pendências: <?= $pendenciasManutencao ?>
        </p>
        <a href="chamados_manutencao.php">Acessar</a>
      </div>
    <?php endif; ?>

    <?php if ($cargo === 'gerente'): ?>
      <div class="card">
        <h2>🏪 Loja</h2>
        <p>Visualize os dados da sua unidade</p>
        <a href="painel_loja_gerente.php">Acessar</a>
      </div>

      <div class="card">
        <h2>🛠️ Tratar Inconformidades</h2>
        <p>Visualize e resolva inconformidades</p>
        <p style="font-weight:bold; color:<?= $pendenciasLoja > 0 ? '#c0392b' : '#2ecc71' ?>">
          <?= $pendenciasLoja > 0 ? '⚠️' : '✅' ?> Pendências: <?= $pendenciasLoja ?>
        </p>
        <a href="painel_tratamento_inconformidades.php">Acessar</a>
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

    <?php if ($acessoTotal || temAcesso($conn, $cpf, 'painel_chamados')): ?>
      <div class="card">
        <h2>📊 Painel de Chamados</h2>
        <p>Visão geral dos chamados por setor</p>
        <p style="font-weight:bold; color:<?= $pendenciasPainel > 0 ? '#34495e' : '#2ecc71' ?>">
          Chamados totais: <?= $pendenciasPainel ?>
        </p>
        <a href="acompanhar_chamados_publico.php">Acessar</a>
      </div>
    <?php endif; ?>

    <?php if ($acessoTotal || temAcesso($conn, $cpf, 'inconformidade_lojas')): ?>
      <div class="card">
        <h2>🛠️ Inconformidades Lojas</h2>
        <p>Verifique problemas reportados pelas lojas</p>
        <p style="font-weight:bold; color:<?= $pendenciasInconformidades > 0 ? '#c0392b' : '#2ecc71' ?>">
          <?= $pendenciasInconformidades > 0 ? '⚠️' : '✅' ?> Pendências: <?= $pendenciasInconformidades ?>
        </p>
        <a href="inconformidade_lojas.php">Acessar</a>
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

<?php include '../includes/scripts.php'; ?>
</body>
</html>
