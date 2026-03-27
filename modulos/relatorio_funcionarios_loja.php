<?php
session_start();
if (!isset($_SESSION['usuario']) || ($_SESSION['perfil'] ?? '') !== 'admin') {
  header('Location: ../index.php');
  exit;
}

$funcionarios     = json_decode(@file_get_contents('../dados/funcionarios.json'), true) ?: [];
$cargosRaw        = json_decode(@file_get_contents('../dados/cargos.json'), true) ?: [];
$dadosGerenciais  = json_decode(@file_get_contents('../dados/gerencial.json'), true) ?: [];

// Extrai cargos do JSON (aceita ["TI", "Gerente"] ou [{"nome":"TI"}])
$cargosDisponiveis = [];
foreach ($cargosRaw as $c) {
  if (is_array($c) && isset($c['nome'])) {
    $cargosDisponiveis[] = $c['nome'];
  } elseif (is_string($c)) {
    $cargosDisponiveis[] = $c;
  }
}
$cargosDisponiveis = array_filter(array_unique($cargosDisponiveis));

// Fallback: deduz cargos dos funcionários se cargos.json estiver vazio
if (empty($cargosDisponiveis)) {
  foreach ($funcionarios as $loja => $lista) {
    foreach ($lista as $f) {
      $cargo = $f['cargo'] ?? '';
      if ($cargo) $cargosDisponiveis[] = $cargo;
    }
  }
  $cargosDisponiveis = array_values(array_unique($cargosDisponiveis));
}


$totaisPorCargo = array_fill_keys($cargosDisponiveis, 0);
$totalAtivosGeral = 0;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>👥 Funcionários por Loja e Cargo</title>
  <link rel="stylesheet" href="../css/style.css">

  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f9f9f9;
      padding: 30px;
      color: #333;
    }

    h2 {
      font-size: 24px;
      margin-bottom: 10px;
    }

    p {
      font-size: 15px;
      margin-bottom: 20px;
    }

    .table-wrap {
      overflow-x: auto;
      border: 1px solid #ddd;
      border-radius: 6px;
      background: #fff;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }

    table {
      border-collapse: collapse;
      width: 100%;
      min-width: 800px;
    }

    th, td {
      border: 1px solid #ccc;
      padding: 10px 8px;
      text-align: center;
      font-size: 14px;
      white-space: nowrap;
    }

    th {
      background: #f0f0f0;
      position: sticky;
      top: 0;
      z-index: 1;
    }

    tr:nth-child(even) {
      background: #fdfdfd;
    }

    tr:hover td {
      background: #f1f7ff;
    }

    .btn {
      margin-top: 30px;
      display: inline-block;
      padding: 10px 20px;
      background: #007bff;
      color: white;
      border-radius: 6px;
      text-decoration: none;
      font-weight: bold;
    }

    .btn:hover {
      background: #0056b3;
    }
  </style>
</head>
<body>

<h2>👥 Funcionários por Loja e Cargo</h2>
<p>Exibe todas as colunas de cargos disponíveis no sistema, com base no cadastro oficial de lojas.</p>

<div class="table-wrap">
  <table>
    <tr>
      <th>Loja</th>
      <?php foreach ($cargosDisponiveis as $cargo): ?>
        <th><?= htmlspecialchars($cargo) ?></th>
      <?php endforeach; ?>
      <th>Ativos</th>
    </tr>

    <?php foreach ($dadosGerenciais as $lojaId => $lojaInfo):
      $lista = $funcionarios[$lojaId] ?? [];
      $contagem = array_fill_keys($cargosDisponiveis, 0);
      $ativos = 0;

      foreach ($lista as $f) {
        $cargo = $f['cargo'] ?? '';
        if (isset($contagem[$cargo])) {
          $contagem[$cargo]++;
        }
        if (!empty($f['ativo'])) {
          $ativos++;
        }
      }

      foreach ($cargosDisponiveis as $cargo) {
        $totaisPorCargo[$cargo] += $contagem[$cargo];
      }
      $totalAtivosGeral += $ativos;
    ?>
      <tr>
        <td><?= htmlspecialchars($lojaInfo['nome'] ?? $lojaId) ?></td>
        <?php foreach ($cargosDisponiveis as $cargo): ?>
          <td><?= $contagem[$cargo] ?></td>
        <?php endforeach; ?>
        <td><strong><?= $ativos ?></strong></td>
      </tr>
    <?php endforeach; ?>

    <tr style="background:#f9f9f9; font-weight:bold;">
      <td>Total</td>
      <?php foreach ($cargosDisponiveis as $cargo): ?>
        <td><?= $totaisPorCargo[$cargo] ?></td>
      <?php endforeach; ?>
      <td><?= $totalAtivosGeral ?></td>
    </tr>
  </table>
</div>
<div style="margin-top: 30px;">
  <a href="../exportacao/exportar_funcionarios_excel.php" class="btn">📥 Exportar Excel</a>
  <a href="../exportacao/exportar_funcionarios_pdf.php" class="btn" style="background:#6c757d;">🖨️ Exportar PDF</a>
</div>




<a href="relatorio_funcionarios.php" class="btn">🔙 Voltar ao submenu</a>

</body>
</html>
