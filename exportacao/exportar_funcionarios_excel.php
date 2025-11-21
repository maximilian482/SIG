<?php
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

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=funcionarios_filtrados.csv');

$output = fopen('php://output', 'w');
$campos = ['Loja', 'Nome', 'Cargo', 'Status', 'CPF', 'Telefone', 'Email', 'Aniversário', 'Contratação', 'Tempo de serviço'];
fputcsv($output, $campos);

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
    $linha = [
      $lojaInfo['nome'] ?? $lojaId,
      $f['nome'] ?? '—',
      $cargo,
      $ativo ? 'Ativo' : 'Inativo',
      $f['cpf'] ?? '—',
      $f['telefone'] ?? '—',
      $f['email'] ?? '—',
      formatarData($f['aniversario'] ?? ''),
      formatarData($contratacao),
      tempoServico($contratacao)
    ];
    fputcsv($output, $linha);

    $totalGeral++;
    $totalPorCargo[$cargo] = ($totalPorCargo[$cargo] ?? 0) + 1;
  }
}

// Totalizador
fputcsv($output, []);
fputcsv($output, ['Total de funcionários:', $totalGeral]);
foreach ($totalPorCargo as $cargo => $qtd) {
  fputcsv($output, ["$cargo:", $qtd]);
}

fclose($output);
