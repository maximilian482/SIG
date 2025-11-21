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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>
<body>

<h1>📌 Acompanhamento de Pendências</h1>
  <p>Olá, <strong><?= htmlspecialchars($usuario) ?></strong>. Abaixo estão os módulos com pendências disponíveis para você:</p>
  
  <div class="menu" style="display: flex; flex-wrap: wrap; gap: 20px;">

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

    <?php if ($cargo === 'gerente' || temAcesso($conn, $cpf, 'painel_tratamento_inconformidades')): ?>
  <div class="card">
    <h2>🛠️ Tratar Inconformidades</h2>
    <p>Visualize e resolva inconformidades</p>
    <p style="font-weight:bold; color:<?= $pendenciasLoja > 0 ? '#c0392b' : '#2ecc71' ?>">
      <?= $pendenciasLoja > 0 ? '⚠️' : '✅' ?> Pendências: <?= $pendenciasLoja ?>
    </p>
    <a href="painel_tratamento_inconformidades.php">Acessar</a>
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

  </div>
</main>

<?php include '../includes/scripts.php'; ?>
</body>
</html>