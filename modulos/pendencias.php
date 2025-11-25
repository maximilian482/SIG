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
<style>
  .card {
    position: relative;
    padding: 16px;
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    width: 280px;
  }
  .card h2 { margin: 0 0 8px; }
  .card p { margin: 6px 0; }

  .badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    padding: 12px 16px;
    border-radius: 12px;
    margin-top: 8px;
    font-size: 1rem;
  }
  .badge .count {
    font-size: 1.25rem;
    line-height: 1;
  }

  /* Estados */
  .badge-ok {
    background: #eafaf1;
    color: #1e8449;
    border: 2px solid #2ecc71;
  }
  .badge-warn {
    background: #fdecea;
    color: #943126;
    border: 2px solid #e74c3c;
    box-shadow: 0 0 0 4px rgba(231,76,60,0.12);
    animation: pulse 1.8s ease-in-out infinite;
  }

  /* Pulso sutil para chamar atenção */
  @keyframes pulse {
    0%   { box-shadow: 0 0 0 4px rgba(231,76,60,0.12); transform: translateZ(0); }
    50%  { box-shadow: 0 0 0 8px rgba(231,76,60,0.08); transform: translateZ(0); }
    100% { box-shadow: 0 0 0 4px rgba(231,76,60,0.12); transform: translateZ(0); }
  }

  /* Layout do menu */
  .menu {
    display: flex; flex-wrap: wrap; gap: 20px;
  }
  .card a {
    display: inline-block;
    margin-top: 10px;
    background: #34495e; color: #fff;
    padding: 8px 12px; border-radius: 8px; text-decoration: none;
  }
  .card a:hover { background: #2c3e50; }
</style>

<h1>📌 Acompanhamento de Pendências</h1>
  <p>Olá, <strong><?= htmlspecialchars($usuario) ?></strong>. Abaixo estão os módulos com pendências disponíveis para você:</p>
  
  <div class="menu" style="display: flex; flex-wrap: wrap; gap: 20px;">

    <?php if (($cargo === 'supervisao' || temAcesso($conn, $cpf, 'chamados_supervisao'))): ?>
      <div class="card">
        <h2>🧭 Chamados Supervisão</h2>
        <p>Gerencie chamados relacionados à supervisão de loja</p>

        <?php $warn = $pendenciasSupervisao > 0; ?>
        <div class="badge <?= $warn ? 'badge-warn pulse' : 'badge-ok' ?>">
          <span><?= $warn ? '⚠️ Pendências' : '✅ Sem pendências' ?></span>
          <span class="count"><?= (int)$pendenciasSupervisao ?></span>
        </div>

        <a href="chamados_supervisao.php">Acessar</a>
      </div>
    <?php endif; ?>


    <?php if (($cargo === 'ti' || temAcesso($conn, $cpf, 'chamados_ti'))): ?>
      <div class="card">
        <h2>🖥️ Chamados TI</h2>
        <p>Gerencie chamados técnicos</p>

        <?php $warn = $pendenciasTI > 0; ?>
        <div class="badge <?= $warn ? 'badge-warn pulse' : 'badge-ok' ?>">
          <span><?= $warn ? '⚠️ Pendências' : '✅ Sem pendências' ?></span>
          <span class="count"><?= (int)$pendenciasTI ?></span>
        </div>

        <a href="chamados_ti.php">Acessar</a>
      </div>
    <?php endif; ?>


    <?php if (($cargo === 'manutencao' || temAcesso($conn, $cpf, 'chamados_manutencao'))): ?>
      <div class="card">
        <h2>🔧 Chamados Manutenção</h2>
        <p>Gerencie chamados de infraestrutura</p>

        <?php $warn = $pendenciasManutencao > 0; ?>
        <div class="badge <?= $warn ? 'badge-warn pulse' : 'badge-ok' ?>">
          <span><?= $warn ? '⚠️ Pendências' : '✅ Sem pendências' ?></span>
          <span class="count"><?= (int)$pendenciasManutencao ?></span>
        </div>

        <a href="chamados_manutencao.php">Acessar</a>
      </div>
    <?php endif; ?>

    <?php if ($cargo === 'gerente' || temAcesso($conn, $cpf, 'painel_tratamento_inconformidades')): ?>
      <div class="card">
        <h2>🛠️ Tratar Inconformidades</h2>
        <p>Visualize e resolva inconformidades</p>

        <?php $warn = $pendenciasLoja > 0; ?>
        <div class="badge <?= $warn ? 'badge-warn pulse' : 'badge-ok' ?>">
          <span><?= $warn ? '⚠️ Pendências' : '✅ Sem pendências' ?></span>
          <span class="count"><?= (int)$pendenciasLoja ?></span>
        </div>

        <a href="painel_tratamento_inconformidades.php">Acessar</a>
      </div>
    <?php endif; ?>



   <?php if ($acessoTotal || temAcesso($conn, $cpf, 'painel_chamados')): ?>
      <div class="card">
        <h2>📊 Painel de Chamados</h2>
        <p>Visão geral dos chamados por setor</p>

        <?php $warn = $pendenciasPainel > 0; ?>
        <div class="badge <?= $warn ? 'badge-warn pulse' : 'badge-ok' ?>">
          <span><?= $warn ? '📈 Chamados totais' : '✅ Nenhum chamado' ?></span>
          <span class="count"><?= (int)$pendenciasPainel ?></span>
        </div>

        <a href="chamados_admin.php">Acessar</a>
      </div>
    <?php endif; ?>


    <?php if ($acessoTotal || temAcesso($conn, $cpf, 'inconformidade_lojas')): ?>
      <div class="card">
        <h2>🏬 Inconformidades Lojas</h2>
        <p>Verifique problemas reportados pelas lojas</p>

        <?php $warn = $pendenciasInconformidades > 0; ?>
        <div class="badge <?= $warn ? 'badge-warn pulse' : 'badge-ok' ?>">
          <span><?= $warn ? '⚠️ Pendências' : '✅ Sem pendências' ?></span>
          <span class="count"><?= (int)$pendenciasInconformidades ?></span>
        </div>

        <a href="inconformidade_lojas.php">Acessar</a>
      </div>
    <?php endif; ?>


  </div>
</main>

<?php include '../includes/scripts.php'; ?>
</body>
</html>