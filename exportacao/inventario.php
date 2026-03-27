<?php
session_start();

$cpf = $_SESSION['cpf'] ?? '';
$cargo = strtolower($_SESSION['cargo'] ?? '');

// Função para verificar acesso
function temAcesso($cpf, $modulo) {
  $acessos = json_decode(@file_get_contents('../dados/acessos_usuarios.json'), true) ?: [];
  return !empty($acessos[$cpf][$modulo]);
}

if (!isset($_SESSION['usuario']) || ($cargo !== 'super' && !temAcesso($cpf, 'relatorios'))) {
  header('Location: ../index.php');
  exit;
}

$lojas      = json_decode(@file_get_contents('../dados/gerencial.json'), true) ?: [];
$inventario = json_decode(@file_get_contents('../dados/inventario.json'), true) ?: [];

/* ========= Filtros ========= */
$lojaSelecionada     = $_GET['loja']        ?? '';
$tipoSelecionado     = $_GET['tipo']        ?? '';
$statusSelecionado   = $_GET['status']      ?? '';
$responsavelSelecionado = $_GET['responsavel'] ?? '';

/* ========= Detecta campos ========= */
$camposDetectados = [];
foreach ($lojas as $lojaId => $info) {
  if ($lojaSelecionada && $lojaId !== $lojaSelecionada) continue;
  $lista = $inventario[$lojaId] ?? [];
  foreach ($lista as $item) {
    foreach ($item as $campo => $valor) {
      $camposDetectados[$campo] = true;
    }
  }
}
$camposDetectados = array_keys($camposDetectados);

/* ========= Filtragem ========= */
$itensFiltrados = [];
foreach ($lojas as $lojaId => $info) {
  if ($lojaSelecionada && $lojaId !== $lojaSelecionada) continue;
  $lista = $inventario[$lojaId] ?? [];

  foreach ($lista as $item) {
    $tipo        = $item['tipo'] ?? '';
    $ativo       = !empty($item['ativo']);
    $responsavel = $item['responsavel'] ?? '';

    if ($tipoSelecionado && $tipo !== $tipoSelecionado) continue;
    if ($statusSelecionado === 'ativo'   && !$ativo) continue;
    if ($statusSelecionado === 'inativo' &&  $ativo) continue;
    if ($responsavelSelecionado && $responsavel !== $responsavelSelecionado) continue;

    $item['loja_nome'] = $info['nome'] ?? $lojaId;
    $itensFiltrados[] = $item;
  }
}

// Totalizador por item

   $totalPorTipo = [];
$valorTotalGeral = 0.0;

foreach ($itensFiltrados as $item) {
  $tipo = $item['tipo'] ?? '—';

  // Quantidade (default 1)
  $qtd = $item['quantidade'] ?? 1;
  if (!is_numeric($qtd)) {
    $qtd = normalizarMoeda($qtd);
  }
  $qtd = max(0, (float)$qtd);

  // Tenta diferentes campos de valor unitário
  $valorUnit = 0.0;
  $possiveisCamposValor = ['valor_unitario', 'valor', 'preco', 'custo_unitario'];
  foreach ($possiveisCamposValor as $campoValor) {
    if (isset($item[$campoValor]) && $item[$campoValor] !== '') {
      $valorUnit = normalizarMoeda($item[$campoValor]);
      break;
    }
  }

  // Caso o item já tenha valor_total, respeita-o (se fizer sentido no teu dado)
  if (isset($item['valor_total']) && $item['valor_total'] !== '') {
    $valorTotalItem = normalizarMoeda($item['valor_total']);
    // Se existir valor_total, usamos ele; senão, calcula por qtd * unitário
    if ($valorTotalItem <= 0 && $valorUnit > 0) {
      $valorTotalItem = $qtd * $valorUnit;
    }
  } else {
    $valorTotalItem = $qtd * $valorUnit;
  }

  if (!isset($totalPorTipo[$tipo])) {
    $totalPorTipo[$tipo] = ['quantidade' => 0.0, 'valor' => 0.0];
  }

  $totalPorTipo[$tipo]['quantidade'] += $qtd;
  $totalPorTipo[$tipo]['valor']      += $valorTotalItem;
  $valorTotalGeral                   += $valorTotalItem;
}


// Normalizador de valor

function normalizarMoeda($v) {
  if ($v === null || $v === '') return 0.0;
  if (is_numeric($v)) return floatval($v);
  // Remove tudo que não for dígito, vírgula ou ponto
  $v = preg_replace('/[^\d,.\-]/', '', (string)$v);
  // Se tem vírgula e ponto, assume vírgula como decimal (pt-BR)
  if (strpos($v, ',') !== false && strpos($v, '.') !== false) {
    $v = str_replace('.', '', $v);     // remove separadores de milhar
    $v = str_replace(',', '.', $v);    // vírgula vira decimal
  } elseif (strpos($v, ',') !== false && strpos($v, '.') === false) {
    // Tem só vírgula: trata como decimal
    $v = str_replace(',', '.', $v);
  }
  return floatval($v);
}

// Formatar moeda

function formatarMoeda($valor) {
  return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

/* ========= Resumo ========= */
$totalItens = count($itensFiltrados);
$valorTotal = 0;
foreach ($itensFiltrados as $item) {
  $qtd = $item['quantidade'] ?? 1;
  $valor = $item['valor_unitario'] ?? 0;
  $valorTotal += $qtd * $valor;
}

/* ========= Query para exportação ========= */
$queryBase = http_build_query([
  'loja' => $lojaSelecionada,
  'tipo' => $tipoSelecionado,
  'status' => $statusSelecionado,
  'responsavel' => $responsavelSelecionado
]);

$tipos = [];
$responsaveis = [];

foreach ($inventario as $lojaId => $lista) {
  foreach ($lista as $item) {
    if (!empty($item['tipo'])) {
      $tipos[] = $item['tipo'];
    }
    if (!empty($item['responsavel'])) {
      $responsaveis[] = $item['responsavel'];
    }
  }
}

$tipos = array_values(array_unique($tipos));
sort($tipos);

$responsaveis = array_values(array_unique($responsaveis));
sort($responsaveis);



?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>📦 Inventário</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<style>
    body { font-family: 'Segoe UI', sans-serif; background: #f9f9f9; padding: 30px; color: #333; }
    h2 { font-size: 24px; margin-bottom: 10px; }
    form { margin-bottom: 30px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); max-width: 640px; }
    label { display: block; margin-top: 10px; font-weight: bold; }
    select { width: 100%; padding: 8px; margin-top: 4px; border-radius: 4px; border: 1px solid #ccc; }
    .btn { margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block; }
    .btn:hover { background: #0056b3; }
    .btn-secondary { background: #6c757d; }
    .btn-secondary:hover { background: #5a6268; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; font-size: 14px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; }
    th { background: #f0f0f0; }
    .table-wrap { overflow-x: auto; margin-top: 20px; }
    small.muted { color: #666; }
</style>
<h2>📦 Inventário</h2>
<p>Filtre os itens por loja, tipo, status e responsável antes de visualizar ou exportar.</p>

<form method="get">
  <label for="loja">Loja</label>
  <select name="loja" id="loja">
    <option value="">Todas</option>
    <?php foreach ($lojas as $id => $info): ?>
      <option value="<?= htmlspecialchars($id) ?>" <?= $id === $lojaSelecionada ? 'selected' : '' ?>>
        <?= htmlspecialchars($info['nome'] ?? $id) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <label for="tipo">Tipo</label>
<select name="tipo" id="tipo">
  <option value="">Todos</option>
  <?php foreach ($tipos as $tipo): ?>
    <option value="<?= htmlspecialchars($tipo) ?>" <?= $tipo === $tipoSelecionado ? 'selected' : '' ?>>
      <?= htmlspecialchars($tipo) ?>
    </option>
  <?php endforeach; ?>
</select>

<label for="responsavel">Responsável</label>
<select name="responsavel" id="responsavel">
  <option value="">Todos</option>
  <?php foreach ($responsaveis as $resp): ?>
    <option value="<?= htmlspecialchars($resp) ?>" <?= $resp === $responsavelSelecionado ? 'selected' : '' ?>>
      <?= htmlspecialchars($resp) ?>
    </option>
  <?php endforeach; ?>
</select>

  <label for="status">Status</label>
  <select name="status" id="status">
    <option value="">Todos</option>
    <option value="ativo" <?= $statusSelecionado === 'ativo' ? 'selected' : '' ?>>Ativos</option>
    <option value="inativo" <?= $statusSelecionado === 'inativo' ? 'selected' : '' ?>>Inativos</option>
  </select>

  <button type="submit" class="btn">🔍 Aplicar Filtro</button>
</form>


<a href="exportar_inventario_excel.php?<?= $queryBase ?>" class="btn">📥 Exportar Excel</a>
<a href="exportar_inventario.pdf.php?
  loja=<?= urlencode($lojaSelecionada) ?>&
  tipo=<?= urlencode($tipoSelecionado) ?>&
  status=<?= urlencode($statusSelecionado) ?>&
  responsavel=<?= urlencode($responsavelSelecionado) ?>&
  usuario=<?= urlencode($usuarioLogado) ?>"
  class="btn btn-secondary">🖨️ Exportar PDF</a>


<a href="index.php" class="btn btn-secondary" style="margin-top:30px;">🔙 Voltar</a>

<div class="table-wrap">
  <table>
    <tr>
      <th>Loja</th>
      <?php foreach ($camposDetectados as $campo): ?>
        <th><?= htmlspecialchars(ucfirst($campo)) ?></th>
      <?php endforeach; ?>
    </tr>

    <?php foreach ($itensFiltrados as $item): ?>
      <tr>
        <td><?= htmlspecialchars($item['loja_nome']) ?></td>
        <?php foreach ($camposDetectados as $campo): ?>
          <?php
            $valor = $item[$campo] ?? '—';
            if (is_bool($valor)) $valor = $valor ? 'Sim' : 'Não';
          ?>
          <td><?= htmlspecialchars((string)$valor) ?></td>
        <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
  </table>

<div style="margin-top: 30px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); max-width: 700px;">
  <strong>📦 Total de produtos exibidos:</strong> <?= count($itensFiltrados) ?><br><br>

  <strong>🧮 Detalhamento por tipo:</strong>
  <table style="width:100%; margin-top:10px; font-size:14px;">
    <tr>
      <th>Tipo</th>
      <th>Quantidade total</th>
      <th>Valor total</th>
    </tr>
    <?php foreach ($totalPorTipo as $tipo => $dados): ?>
      <tr>
        <td><?= htmlspecialchars($tipo) ?></td>
        <td><?= $dados['quantidade'] ?></td>
        <td>R$ <?= number_format($dados['valor'], 2, ',', '.') ?></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <br>
  <strong>💰 Valor total estimado:</strong> R$ <?= number_format($valorTotalGeral, 2, ',', '.') ?>
</div>


</body>
</html>
