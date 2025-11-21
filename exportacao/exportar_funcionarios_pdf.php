<?php
require('../vendor/autoload.php'); // ou ajuste para lib/dompdf/autoload.inc.php se for manual

use Dompdf\Dompdf;

$funcionarios     = json_decode(@file_get_contents('../dados/funcionarios.json'), true) ?: [];
$dadosGerenciais  = json_decode(@file_get_contents('../dados/gerencial.json'), true) ?: [];

$lojaFiltro   = $_GET['loja'] ?? '';
$cargoFiltro  = $_GET['cargo'] ?? '';
$statusFiltro = $_GET['status'] ?? '';

function parseData($valor) {
  if (!$valor || !is_string($valor)) return null;
  $valor = preg_replace('/\s+\d{2}:\d{2}(:\d{2})?$/', '', trim($valor));
  $valor = str_replace('/', '-', $valor);
  try {
    return new DateTime($valor);
  } catch (Exception $e) {
    return null;
  }
}

function formatarData($valor) {
  $dt = parseData($valor);
  return $dt ? $dt->format('d-m-Y') : '—';
}

function tempoServico($valor) {
  $dt = parseData($valor);
  if (!$dt) return '—';
  $hoje = new DateTime();
  $dif = $dt->diff($hoje);
  $anos = $dif->y;
  $meses = $dif->m;
  if ($anos === 0 && $meses === 0) return 'Menos de 1 mês';
  $txt = '';
  if ($anos > 0) $txt .= "$anos ano" . ($anos > 1 ? 's' : '');
  if ($meses > 0) $txt .= ($txt ? ' e ' : '') . "$meses mês" . ($meses > 1 ? 'es' : '');
  return $txt;
}

// Detecta campo de contratação
$campoContratacao = null;
foreach (['contratacao', 'admissao', 'data_contratacao'] as $possivel) {
  foreach ($funcionarios as $loja => $lista) {
    foreach ($lista as $f) {
      if (isset($f[$possivel])) {
        $campoContratacao = $possivel;
        break 2;
      }
    }
  }
}

// Gera HTML da tabela
ob_start();
echo "<h2 style='text-align:center;'>Relatório de Funcionários</h2>";
echo "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse; width:100%; font-size:12px;'>";
echo "<tr style='background:#f0f0f0;'>
        <th>Loja</th><th>Nome</th><th>Cargo</th><th>Status</th>
        <th>CPF</th><th>Telefone</th><th>Email</th>
        <th>Aniversário</th><th>Endereço</th><th>Contratação</th><th>Tempo de serviço</th>
      </tr>";

$totalPorCargo = [];
$totalGeral = 0;

foreach ($dadosGerenciais as $lojaId => $lojaInfo) {
  $lista = $funcionarios[$lojaId] ?? [];

  foreach ($lista as $f) {
    $cargo = $f['cargo'] ?? '—';
    $ativo = !empty($f['ativo']);

    if ($lojaFiltro && $lojaId !== $lojaFiltro) continue;
    if ($cargoFiltro && $cargo !== $cargoFiltro) continue;
    if ($statusFiltro === 'ativo' && !$ativo) continue;
    if ($statusFiltro === 'inativo' && $ativo) continue;

    $contratacao = $campoContratacao ? $f[$campoContratacao] ?? '' : '';
    echo "<tr>";
    echo "<td>" . htmlspecialchars($lojaInfo['nome'] ?? $lojaId) . "</td>";
    echo "<td>" . htmlspecialchars($f['nome'] ?? '—') . "</td>";
    echo "<td>" . htmlspecialchars($cargo) . "</td>";
    echo "<td>" . ($ativo ? 'Ativo' : 'Inativo') . "</td>";
    echo "<td>" . htmlspecialchars($f['cpf'] ?? '—') . "</td>";
    echo "<td>" . htmlspecialchars($f['telefone'] ?? '—') . "</td>";
    echo "<td>" . htmlspecialchars($f['email'] ?? '—') . "</td>";
    echo "<td>" . formatarData($f['aniversario'] ?? '') . "</td>";
    echo "<td>" . htmlspecialchars($f['endereco'] ?? '—') . "</td>";
    echo "<td>" . formatarData($contratacao) . "</td>";    
    echo "<td>" . tempoServico($contratacao) . "</td>";
    echo "</tr>";

    $totalGeral++;
    $totalPorCargo[$cargo] = ($totalPorCargo[$cargo] ?? 0) + 1;
  }
}
echo "</table><br>";
echo "<strong>Total de funcionários:</strong> $totalGeral<br>";
echo "<strong>Distribuição por cargo:</strong><ul>";
foreach ($totalPorCargo as $cargo => $qtd) {
  echo "<li>$cargo: $qtd</li>";
}
echo "</ul>";

$html = ob_get_clean();

// Gera PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('funcionarios_filtrados.pdf', ['Attachment' => true]);
