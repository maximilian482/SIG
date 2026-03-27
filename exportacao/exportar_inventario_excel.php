<?php
require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

session_start();
date_default_timezone_set('America/Sao_Paulo');

// Dados do usuário
$usuario = $_SESSION['usuario'] ?? '—';
$dataGeracao = date('d/m/Y');
$horaGeracao = date('H:i');

// Filtros
$loja        = $_GET['loja']        ?? '';
$tipo        = $_GET['tipo']        ?? '';
$status      = $_GET['status']      ?? '';
$responsavel = $_GET['responsavel'] ?? '';

// Carrega dados
$inventario = json_decode(@file_get_contents('../dados/inventario.json'), true) ?: [];
$lojas      = json_decode(@file_get_contents('../dados/gerencial.json'), true) ?: [];

// Função para normalizar moeda
function normalizarMoeda($v) {
  if (is_numeric($v)) return (float)$v;
  $v = preg_replace('/[^\d,.\-]/', '', (string)$v);
  $v = str_replace('.', '', $v);
  $v = str_replace(',', '.', $v);
  return (float)$v;
}

// Filtragem
$filtrados = [];
foreach ($inventario as $lojaId => $lista) {
  if ($loja && $lojaId !== $loja) continue;
  if (!is_array($lista)) continue;

  foreach ($lista as $item) {
    if (!is_array($item)) continue;

    $ok = true;
    if ($tipo        && ($item['tipo'] ?? '') !== $tipo)        $ok = false;
    if ($status      && (($item['ativo'] ?? false) ? 'ativo' : 'inativo') !== $status) $ok = false;
    if ($responsavel && ($item['responsavel'] ?? '') !== $responsavel) $ok = false;

    if ($ok) {
      $item['loja_nome'] = $lojas[$lojaId]['nome'] ?? $lojaId;
      $filtrados[] = $item;
    }
  }
}

if (empty($filtrados)) {
  die('Nenhum item encontrado com os filtros aplicados.');
}

// Detecta campos
$camposDetectados = [];
foreach ($filtrados as $item) {
  foreach ($item as $campo => $valor) {
    $camposDetectados[$campo] = true;
  }
}
$camposDetectados = array_keys($camposDetectados);
$camposMonetarios = ['valor', 'valor_unitario', 'preco', 'custo_unitario'];

// Cria planilha
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$linha = 1;
$formatoMoeda = 'R$ #,##0.00';

// Cabeçalho
$sheet->setCellValue("A{$linha}", "Relatório de Inventário");
$linha++;
$sheet->setCellValue("A{$linha}", "Gerado por: $usuario");
$sheet->setCellValue("C{$linha}", "Data: $dataGeracao");
$sheet->setCellValue("D{$linha}", "Hora: $horaGeracao");
$linha++;

// Filtros aplicados
$filtros = [];
if ($loja)        $filtros[] = "Loja: " . ($lojas[$loja]['nome'] ?? $loja);
if ($tipo)        $filtros[] = "Tipo: $tipo";
if ($status)      $filtros[] = "Status: $status";
if ($responsavel) $filtros[] = "Responsável: $responsavel";
$sheet->setCellValue("A{$linha}", "Filtros aplicados: " . implode(' | ', $filtros));
$linha++;

// Tabela principal
$coluna = 'A';
foreach ($camposDetectados as $campo) {
  $sheet->setCellValue("{$coluna}{$linha}", ucfirst($campo));
  $coluna++;
}
$linha++;

foreach ($filtrados as $item) {
  $coluna = 'A';
  foreach ($camposDetectados as $campo) {
    $valor = $item[$campo] ?? '';
    $celula = "{$coluna}{$linha}";

    if (in_array($campo, $camposMonetarios)) {
      $valor = normalizarMoeda($valor);
      $sheet->setCellValue($celula, $valor);
      $sheet->getStyle($celula)->getNumberFormat()->setFormatCode($formatoMoeda);
    } else {
      $sheet->setCellValue($celula, $valor);
    }

    $coluna++;
  }
  $linha++;
}

// Totalizador por tipo
$linha++;
$sheet->setCellValue("A{$linha}", "Totalizador por Tipo");
$linha++;
$sheet->setCellValue("A{$linha}", "Tipo");
$sheet->setCellValue("B{$linha}", "Quantidade");
$sheet->setCellValue("C{$linha}", "Valor Total");
$linha++;

$totalPorTipo = [];
$valorTotalGeral = 0;
foreach ($filtrados as $item) {
  $tipoItem = $item['tipo'] ?? '—';
  $qtd = normalizarMoeda($item['quantidade'] ?? 1);
  $valorUnit = 0.0;
  foreach ($camposMonetarios as $cv) {
    if (isset($item[$cv]) && $item[$cv] !== '') {
      $valorUnit = normalizarMoeda($item[$cv]);
      break;
    }
  }
  $valorTotal = $qtd * $valorUnit;
  $totalPorTipo[$tipoItem]['quantidade'] = ($totalPorTipo[$tipoItem]['quantidade'] ?? 0) + $qtd;
  $totalPorTipo[$tipoItem]['valor'] = ($totalPorTipo[$tipoItem]['valor'] ?? 0) + $valorTotal;
  $valorTotalGeral += $valorTotal;
}

foreach ($totalPorTipo as $tipo => $dados) {
  $sheet->setCellValue("A{$linha}", $tipo);
  $sheet->setCellValue("B{$linha}", $dados['quantidade']);
  $sheet->setCellValue("C{$linha}", $dados['valor']);
  $sheet->getStyle("C{$linha}")->getNumberFormat()->setFormatCode($formatoMoeda);
  $linha++;
}

$linha++;
$sheet->setCellValue("A{$linha}", "Valor total estimado:");
$sheet->setCellValue("B{$linha}", $valorTotalGeral);
$sheet->getStyle("B{$linha}")->getNumberFormat()->setFormatCode($formatoMoeda);

// Exporta
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="inventario.xlsx"');
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
