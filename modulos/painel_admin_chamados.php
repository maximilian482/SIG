<?php
session_start();

if ($_SESSION['perfil'] !== 'admin') {
  header('Location: ../index.php');
  exit;
}

if (!isset($_SESSION['usuario'])) {
  header('Location: ../login.php');
  exit;
}

function tempoAberto($dataAbertura, $dataEncerramento = null) {
  $inicio = strtotime($dataAbertura);
  $fim = $dataEncerramento ? strtotime($dataEncerramento) : time();

  if (!$inicio || !$fim) return '—';

  $diff = $fim - $inicio;
  $dias = floor($diff / 86400);
  $horas = floor(($diff % 86400) / 3600);
  $min = floor(($diff % 3600) / 60);

  if ($dias > 0) return "{$dias}d {$horas}h";
  if ($horas > 0) return "{$horas}h {$min}m";
  return "{$min}m";
}



function normalizar($texto) {
  return strtolower(preg_replace('/[áàâãä]/u', 'a',
         preg_replace('/[éèêë]/u', 'e',
         preg_replace('/[íìîï]/u', 'i',
         preg_replace('/[óòôõö]/u', 'o',
         preg_replace('/[úùûü]/u', 'u',
         preg_replace('/[ç]/u', 'c', $texto)))))));
}


$usuario = $_SESSION['usuario'];
$cargo   = strtolower(trim($_SESSION['cargo'] ?? ''));
$loja    = $_SESSION['loja'] ?? '';
$perfil  = $_SESSION['perfil'] ?? 'padrao';

$chamados = json_decode(@file_get_contents('../dados/chamados.json'), true);
$chamados = is_array($chamados) ? $chamados : [];

// Filtros via GET
$filtroStatus = strtolower(trim($_GET['status'] ?? ''));
$filtroSetor  = strtolower(trim($_GET['setor'] ?? ''));
$filtroLoja   = trim($_GET['loja'] ?? '');

// Lista fixa de status
$listaStatus = ['aberto', 'em andamento', 'aguardando avaliação', 'reaberto', 'encerrado'];

// Contadores
$totalPorSetor  = ['ti' => 0, 'manutencao' => 0, 'supervisao' => 0];
$totalPorStatus = array_fill_keys($listaStatus, 0);
$totalPorLoja   = [];

// Filtra os chamados
$chamadosFiltrados = [];

foreach ($chamados as $c) {
  $setor  = normalizar($c['setor_destino'] ?? '');
  $status = strtolower(trim($c['status'] ?? ''));
  $lojaCh = trim($c['loja_origem'] ?? '');

  if (!in_array($setor, ['ti', 'manutencao', 'supervisao'])) continue;
  if ($filtroStatus === '' && $status === 'encerrado') continue;
  if ($filtroStatus && $filtroStatus !== $status) continue;
  if ($filtroSetor  && $filtroSetor !== $setor) continue;
  if ($filtroLoja   && $filtroLoja !== $lojaCh) continue;

  $chamadosFiltrados[] = $c;
}

// Reconta com base no que está visível (respeita filtros atuais)
$totalPorSetor  = ['ti' => 0, 'manutencao' => 0, 'supervisao' => 0];
$totalPorStatus = array_fill_keys($listaStatus, 0);
$totalPorLoja   = [];

foreach ($chamadosFiltrados as $c) {
  $setor  = normalizar($c['setor_destino'] ?? '');
  $status = strtolower(trim($c['status'] ?? ''));
  $lojaCh = trim($c['loja_origem'] ?? '');

  if (isset($totalPorSetor[$setor])) {
    $totalPorSetor[$setor]++;
  }

  if (isset($totalPorStatus[$status])) {
    $totalPorStatus[$status]++;
  } else {
    // Captura status inesperado
    $totalPorStatus[$status] = 1;
  }

  $totalPorLoja[$lojaCh] = ($totalPorLoja[$lojaCh] ?? 0) + 1;
}


// Ordena por status e data
usort($chamadosFiltrados, function ($a, $b) {
  $statusA = strtolower(trim($a['status'] ?? ''));
  $statusB = strtolower(trim($b['status'] ?? ''));

  $encerradoA = $statusA === 'encerrado';
  $encerradoB = $statusB === 'encerrado';

  // Encerrados vão para o final
  if ($encerradoA && !$encerradoB) return 1;
  if (!$encerradoA && $encerradoB) return -1;

  // Ordena por data de abertura
  return strtotime($a['data_abertura'] ?? '') <=> strtotime($b['data_abertura'] ?? '');
});



// Paginação
$paginaAtual     = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$limite          = isset($_GET['limite']) ? max(10, intval($_GET['limite'])) : 10;
$totalFiltrados  = count($chamadosFiltrados);
$inicio          = ($paginaAtual - 1) * $limite;
$listaPaginada   = array_slice($chamadosFiltrados, $inicio, $limite);
$totalPaginas    = max(1, ceil($totalFiltrados / $limite));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Chamados</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<h2>📋 Acompanhamento de Chamados</h2>
<p>Visualize os chamados abertos e encerrados dos setores de TI, Manutenção e Supervisão.</p>

<form method="GET" style="margin-bottom:20px;">
  <label>Status:</label>
  <select name="status">
    <option value="">Todos</option>
    <?php foreach ($listaStatus as $st): ?>
      <option value="<?= $st ?>" <?= $filtroStatus === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
    <?php endforeach; ?>
  </select>

  <label style="margin-left:16px;">Setor:</label>
  <select name="setor">
    <option value="">Todos</option>
    <?php foreach ($totalPorSetor as $setor => $_): ?>
      <option value="<?= $setor ?>" <?= $filtroSetor === $setor ? 'selected' : '' ?>><?= ucfirst($setor) ?></option>
    <?php endforeach; ?>
  </select>

  <label style="margin-left:16px;">Loja:</label>
  <select name="loja">
    <option value="">Todas</option>
    <?php foreach ($totalPorLoja as $lojaOpt => $qtd): ?>
      <option value="<?= htmlspecialchars($lojaOpt) ?>" <?= $filtroLoja === $lojaOpt ? 'selected' : '' ?>>
        <?= htmlspecialchars($lojaOpt) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <button type="submit" style="margin-left:8px;">🔍 Filtrar</button>
</form>

<div style="margin-bottom:20px;">
  <strong>Total por setor:</strong>
  <?php foreach ($totalPorSetor as $setor => $qtd): ?>
    <?= ucfirst($setor) ?> (<?= $qtd ?>) |
  <?php endforeach; ?>
  <strong>Status:</strong>
  <?php foreach ($totalPorStatus as $st => $qtd): ?>
    <?= ucfirst($st) ?> (<?= $qtd ?>) |
  <?php endforeach; ?>
</div>

<table>
  <tr>
    <th>#</th>
    <th>Setor</th>
    <th>Solicitante</th>
    <th>Loja</th>
    <th>Título</th>
    <th>Descrição</th>
    <th>Tempo aberto</th>
    <th>Status</th>
  </tr>

  <?php if (count($listaPaginada) === 0): ?>
    <tr><td colspan="9" style="text-align:center;">Nenhum chamado encontrado.</td></tr>
  <?php else: ?>
    <?php
    $codigoGlobal = $inicio + 1;
    foreach ($listaPaginada as $c):
      $status = strtolower(trim($c['status'] ?? ''));
      $setorChamado = strtolower(trim($c['setor_destino'] ?? ''));
      $usuarioPodeTratar = ($setorChamado === $cargo);
      $corStatus = match ($status) {
        'aberto' => 'background:#fff3cd; color:#856404;',
        'em andamento' => 'background:#cce5ff; color:#004085;',
        'aguardando avaliação' => 'background:#ffeeba; color:#856404;',
        'reaberto' => 'background:#f8d7da; color:#721c24;',
        'encerrado' => 'background:#d4edda; color:#155724;',
        default => '',
      };
    ?>
    <tr>
      <td><?= $codigoGlobal++ ?></td>
      <td><?= htmlspecialchars($c['setor_destino'] ?? '') ?></td>
      <td><?= htmlspecialchars($c['usuario_solicitante'] ?? '—') ?></td>
      <td><?= htmlspecialchars($c['loja_origem'] ?? '—') ?></td>
      <td><?= htmlspecialchars($c['titulo'] ?? '') ?></td>
      <td><?= htmlspecialchars($c['descricao'] ?? '') ?></td>
      <td><?= tempoAberto($c['data_abertura'] ?? '', $c['data_avaliacao'] ?? '') ?></td>
      <td style="<?= $corStatus ?> padding:4px; border-radius:4px;"><?= ucfirst($status) ?></td>
    </tr>
    <?php endforeach; ?>
  <?php endif; ?>
</table>

<?php if ($totalPaginas > 1): ?>
  <div style="margin-top:20px; text-align:center;">
    <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
      <?php
        $params = $_GET;
        $params['pagina'] = $p;
        $query = http_build_query($params);
      ?>
      <a href="?<?= $query ?>" style="margin:0 6px; padding:6px 12px; border:1px solid #ccc; border-radius:4px;
        <?= $p === $paginaAtual ? 'background:#007bff; color:#fff;' : 'background:#f8f9fa; color:#333;' ?>">
        <?= $p ?>
      </a>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<a class="btn" href="../index.php" style="margin-top:20px;">🔙 Voltar ao painel</a>

<script>
function abrirModalFecharChamado(id) {
document.getElementById('fecharChamadoId').value = id;
document.getElementById('fecharChamadoSolucao').value = '';
document.getElementById('modalFecharChamado').style.display = 'block';
}
function fecharModalFecharChamado() {
document.getElementById('modalFecharChamado').style.display = 'none';
}
</script>


</body>
</html>