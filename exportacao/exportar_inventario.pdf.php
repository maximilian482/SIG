<?php


declare(strict_types=1);

require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Configurações iniciais
 */
date_default_timezone_set('America/Sao_Paulo');

session_start();

$logoPath = realpath(__DIR__ . '/logo_empresa.jpg');
$logoData = base64_encode(file_get_contents($logoPath));
$logoMime = mime_content_type($logoPath);
$logoSrc  = "data:$logoMime;base64,$logoData";


/**
 * Data/hora e usuário
 */
$agora        = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
$dataGeracao  = $agora->format('d/m/Y');
$horaGeracao  = $agora->format('H:i');

// Captura o usuário (GET primeiro, depois sessão). Se nada vier, usa "—".
$usuarioDetectado = $_GET['usuario']
  ?? ($_SESSION['usuario'] ?? '-')
  ?? '';


$usuario = trim((string)($_SESSION['usuario'] ?? ''));
if ($usuario === '') {
  $usuario = '—';
}

/**
 * Parâmetros de filtro
 */
$loja        = $_GET['loja']        ?? '';
$tipo        = $_GET['tipo']        ?? '';
$status      = $_GET['status']      ?? '';
$responsavel = $_GET['responsavel'] ?? '';

/**
 * Carrega dados
 */
$inventario = json_decode(@file_get_contents('../dados/inventario.json'), true) ?: [];
$lojas      = json_decode(@file_get_contents('../dados/gerencial.json'), true) ?: [];

/**
 * Utilidades
 */
function normalizarMoeda($v): float {
  if ($v === null) return 0.0;
  if (is_numeric($v)) return (float)$v;
  $v = (string)$v;
  // Remove qualquer coisa que não seja dígito, ponto, vírgula ou sinal
  $v = preg_replace('/[^\d,.\-]/', '', $v);
  // Remove separadores de milhar e padroniza decimal para ponto
  $v = str_replace('.', '', $v);
  $v = str_replace(',', '.', $v);
  // Converte para float
  return (float)$v;
}

/**
 * Filtragem
 */
$filtrados = [];

foreach ($inventario as $lojaId => $lista) {
  if ($loja && (string)$lojaId !== (string)$loja) continue;
  if (!is_array($lista)) continue;

  foreach ($lista as $item) {
    if (!is_array($item)) continue;

    $ok = true;

    if ($tipo && (string)($item['tipo'] ?? '') !== (string)$tipo) {
      $ok = false;
    }

    if ($ok && $status) {
      // Status: considera campo booleano 'ativo' ou string 'status'
      $statusItem = isset($item['ativo'])
        ? (($item['ativo'] ? 'ativo' : 'inativo'))
        : (string)($item['status'] ?? '');
      if ((string)$statusItem !== (string)$status) {
        $ok = false;
      }
    }

    if ($ok && $responsavel && (string)($item['responsavel'] ?? '') !== (string)$responsavel) {
      $ok = false;
    }

    if ($ok) {
      $item['loja'] = $lojaId;
      $item['loja_nome'] = $lojas[$lojaId]['nome'] ?? $lojaId;
      $filtrados[] = $item;
    }
  }
}

if (empty($filtrados)) {
  die('Nenhum item encontrado com os filtros aplicados.');
}

/**
 * Detecção de colunas com prioridade
 * Garantimos a presença e a ordem de colunas principais da página.
 */
$prioridadeCampos = [
  'loja_nome', 'controle', 'tipo', 'descricao', 'modelo', 'numero_serie',
  'quantidade', 'valor_unitario', 'valor', 'preco', 'custo_unitario',
  'ativo', 'status', 'responsavel'
];

$camposDetectadosMap = [];
foreach ($filtrados as $item) {
  foreach ($item as $campo => $valor) {
    $camposDetectadosMap[$campo] = true;
  }
}
$camposDetectados = array_keys($camposDetectadosMap);

// Reorganiza para priorizar exibição igual à tela
$ordenados = [];
foreach ($prioridadeCampos as $c) {
  if (isset($camposDetectadosMap[$c])) $ordenados[] = $c;
}
foreach ($camposDetectados as $c) {
  if (!in_array($c, $ordenados, true)) $ordenados[] = $c;
}
$camposDetectados = $ordenados;

/**
 * Totalizadores por tipo e total geral
 */
$totalPorTipo = [];
$valorTotalGeral = 0.0;

foreach ($filtrados as $item) {
  $tipoItem = $item['tipo'] ?? '—';

  // quantidade
  $qtd = normalizarMoeda($item['quantidade'] ?? 1);

  // valor unitário: busca flexível
  $valorUnit = 0.0;
  $campoUsado = null;
  foreach (['valor_unitario', 'valor', 'preco', 'custo_unitario'] as $cv) {
    if (isset($item[$cv]) && $item[$cv] !== '') {
      $valorUnit = normalizarMoeda($item[$cv]);
      $campoUsado = $cv;
      break;
    }
  }

  $valorTotal = $qtd * $valorUnit;

  if (!isset($totalPorTipo[$tipoItem])) {
    $totalPorTipo[$tipoItem] = ['quantidade' => 0.0, 'valor' => 0.0];
  }

  $totalPorTipo[$tipoItem]['quantidade'] += $qtd;
  $totalPorTipo[$tipoItem]['valor']      += $valorTotal;
  $valorTotalGeral                       += $valorTotal;
}

/**
 * Monta HTML
 */
ob_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #222; }
    h2 { margin: 0 0 6px 0; }
    .meta { margin: 0 0 10px 0; }
    .meta strong { font-weight: 600; }
    table { border-collapse: collapse; width: 100%; margin-top: 8px; }
    th, td { border: 1px solid #ccc; padding: 6px; text-align: left; vertical-align: top; }
    th { background: #f0f0f0; }
    .totais { margin-top: 18px; }
  </style>
</head>
<body>
  <div style="display: flex; align-items: center; justify-content: space-between;">
  <img src="<?php echo $logoSrc; ?>" alt="Logotipo da Empresa" style="height: 120px;">

  <div style="text-align: right; font-size: 11px;">
    <strong>Gerado por:</strong> <?= htmlspecialchars($usuario) ?><br>
    <strong>Data:</strong> <?= $dataGeracao ?> &nbsp;&nbsp;
    <strong>Hora:</strong> <?= $horaGeracao ?>
  </div>
</div>

<h2>Relatório de Inventário</h2>

<p class="meta">
  <strong>Gerado por:</strong> <?= htmlspecialchars($usuario) ?><br>
  <strong>Data:</strong> <?= $dataGeracao ?> &nbsp;&nbsp;
  <strong>Hora:</strong> <?= $horaGeracao ?>
</p>

<p class="meta">
  <strong>Filtros aplicados:</strong>
  <?php if ($loja): ?>
    &nbsp;Loja: <?= htmlspecialchars($lojas[$loja]['nome'] ?? $loja) ?>
  <?php endif; ?>
  <?php if ($tipo): ?>
    &nbsp;|&nbsp; Tipo: <?= htmlspecialchars($tipo) ?>
  <?php endif; ?>
  <?php if ($status): ?>
    &nbsp;|&nbsp; Status: <?= htmlspecialchars($status) ?>
  <?php endif; ?>
  <?php if ($responsavel): ?>
    &nbsp;|&nbsp; Responsável: <?= htmlspecialchars($responsavel) ?>
  <?php endif; ?>
</p>

<p class="meta">
  <strong>Total de itens exibidos:</strong> <?= count($filtrados) ?>
</p>

<table>
  <tr>
    <?php foreach ($camposDetectados as $campo): ?>
      <th><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $campo))) ?></th>
    <?php endforeach; ?>
    <?php
      // Se não existir coluna valor_total, criaremos uma derivada ao exibir
      $exibirValorTotalDerivado = !in_array('valor_total', $camposDetectados, true);
      if ($exibirValorTotalDerivado) {
        echo '<th>Valor total</th>';
      }
    ?>
  </tr>

  <?php foreach ($filtrados as $item): ?>
    <tr>
      <?php
        // Calcula valor total derivado por linha se necessário
        $qtdLinha = normalizarMoeda($item['quantidade'] ?? 1);
        $valorUnitLinha = 0.0;
        foreach (['valor_unitario', 'valor', 'preco', 'custo_unitario'] as $cv) {
          if (isset($item[$cv]) && $item[$cv] !== '') {
            $valorUnitLinha = normalizarMoeda($item[$cv]);
            break;
          }
        }
        $valorTotalLinha = $qtdLinha * $valorUnitLinha;
      ?>

      <?php foreach ($camposDetectados as $campo): ?>
        <?php
          $valor = $item[$campo] ?? '—';

          // Formatação específica
          if (in_array($campo, ['valor', 'valor_unitario', 'preco', 'custo_unitario', 'valor_total'], true)) {
            $num = normalizarMoeda($valor);
            $valor = 'R$ ' . number_format($num, 2, ',', '.');
          } elseif ($campo === 'quantidade') {
            $num = normalizarMoeda($valor);
            $valor = number_format($num, 0, ',', '.');
          } elseif ($campo === 'ativo') {
            $valor = !empty($item['ativo']) ? 'Ativo' : 'Inativo';
          }

          // Segurança
          $valor = (string)$valor;
        ?>
        <td><?= htmlspecialchars($valor) ?></td>
      <?php endforeach; ?>

      <?php if ($exibirValorTotalDerivado): ?>
        <td><?= 'R$ ' . number_format($valorTotalLinha, 2, ',', '.') ?></td>
      <?php endif; ?>
    </tr>
  <?php endforeach; ?>
</table>

<div class="totais">
  <h3>Totalizador por tipo</h3>
  <table>
    <tr>
      <th>Tipo</th>
      <th>Quantidade total</th>
      <th>Valor total</th>
    </tr>
    <?php foreach ($totalPorTipo as $tipoItem => $dados): ?>
      <tr>
        <td><?= htmlspecialchars((string)$tipoItem) ?></td>
        <td><?= number_format($dados['quantidade'], 0, ',', '.') ?></td>
        <td><?= 'R$ ' . number_format($dados['valor'], 2, ',', '.') ?></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <p style="margin-top: 10px;">
    <strong>Valor total estimado:</strong> <?= 'R$ ' . number_format($valorTotalGeral, 2, ',', '.') ?>
  </p>
</div>

</body>
</html>
<?php
$html = ob_get_clean();

/**
 * Gera PDF
 */
$options = new Options();
$options->set('defaultFont', 'Segoe UI'); // fonte padrão
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("inventario.pdf", ["Attachment" => true]);
