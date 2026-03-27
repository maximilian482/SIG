<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

date_default_timezone_set('America/Sao_Paulo');

// Verifica permissão
$cpf   = $_SESSION['cpf']  ?? '';
$cargo = strtolower($_SESSION['cargo'] ?? '');

if (!temAcesso($conn, $cpf, 'gestao_painel_chamados') && !in_array($cargo, ['super', 'ceo'])) {
    echo "<h2 style='color:red; text-align:center; margin-top:40px;'>❌ Você não tem permissão para acessar o Painel de Chamados.</h2>";
    exit;
}

// Filtros
$filtroDestino = trim($_GET['destino'] ?? '');
$filtroTempo   = trim($_GET['tempo']   ?? '');
$filtroBusca   = trim($_GET['busca']   ?? '');

$paginaAtual = max(1, intval($_GET['pagina'] ?? 1));
$porPagina   = 10;

// Parte comum da query
$sqlFrom = "
  FROM chamados c
  LEFT JOIN funcionarios f ON f.id = c.solicitante_id
  LEFT JOIN lojas lo ON lo.id = c.loja_origem
  LEFT JOIN lojas ld ON ld.id = c.loja_destino
  LEFT JOIN setores s ON s.id = c.setor_destino
  WHERE 1=1
    AND LOWER(TRIM(c.status)) <> 'encerrado'
";

// WHERE dinâmico
$where  = '';
$params = [];
$types  = '';

// Filtro por destino (setor ou loja)
if ($filtroDestino !== '') {

    if (is_numeric($filtroDestino)) {
        // Loja
        $where    .= " AND c.loja_destino = ? ";
        $params[]  = intval($filtroDestino);
        $types    .= 'i';

    } else {
        // Setor
        $where    .= " AND s.nome = ? ";
        $params[]  = $filtroDestino;
        $types    .= 's';
    }
}

// Filtro por tempo (48h / 72h)
if ($filtroTempo !== '') {
    $where    .= " AND TIMESTAMPDIFF(HOUR, c.data_abertura, NOW()) >= ? ";
    $params[]  = intval($filtroTempo);
    $types    .= 'i';
}

// Filtro de busca
if ($filtroBusca !== '') {
    $like      = "%".$filtroBusca."%";
    $where    .= " AND (c.codigo_chamado LIKE ? OR c.titulo LIKE ?) ";
    $params[]  = $like;
    $params[]  = $like;
    $types    .= 'ss';
}

// 1) Contar total
$sqlCount  = "SELECT COUNT(*) AS total " . $sqlFrom . $where;
$stmtCount = $conn->prepare($sqlCount);
if (!empty($params)) $stmtCount->bind_param($types, ...$params);
$stmtCount->execute();
$totalRegistros = $stmtCount->get_result()->fetch_assoc()['total'] ?? 0;
$totalPaginas   = max(1, ceil($totalRegistros / $porPagina));

// 2) Buscar página atual
$offset    = ($paginaAtual - 1) * $porPagina;
$sqlPagina = "
    SELECT 
        c.*, 
        f.nome AS solicitante_nome, 
        lo.nome AS loja_origem_nome, 
        ld.nome AS loja_destino_nome,
        s.nome AS setor_destino_nome
    " . $sqlFrom . $where . " 
    ORDER BY c.data_abertura ASC 
    LIMIT ? OFFSET ?
";

$stmt           = $conn->prepare($sqlPagina);
$paramsPagina   = $params;
$typesPagina    = $types . 'ii';
$paramsPagina[] = $porPagina;
$paramsPagina[] = $offset;

$stmt->bind_param($typesPagina, ...$paramsPagina);
$stmt->execute();
$chamados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3) Buscar todos os registros (para alerta global >48h)
$sqlTodos = "
    SELECT c.id, c.status, c.data_abertura
    " . $sqlFrom . $where;

$stmtAll  = $conn->prepare($sqlTodos);
if (!empty($params)) $stmtAll->bind_param($types, ...$params);
$stmtAll->execute();
$chamadosTodos = $stmtAll->get_result()->fetch_all(MYSQLI_ASSOC);

// 4) IDs com alerta >48h
$alertaChamados     = [];
$statusesEmAberto   = ['aberto','em andamento','reaberto','aguardando avaliacao','aguardando avaliação'];

foreach ($chamados as $c) {
    $statusLower = strtolower(trim($c['status'] ?? ''));
    if (in_array($statusLower, $statusesEmAberto, true)) {
        $aberturaTs = strtotime($c['data_abertura'] ?? '');
        if ($aberturaTs && (time() - $aberturaTs) > 48 * 3600) {
            $alertaChamados[] = $c['id'];
        }
    }
}

// 5) Total global de não encerrados >48h
$abertos48hTotal = 0;
foreach ($chamadosTodos as $c) {
    $statusLower = strtolower(trim($c['status'] ?? ''));
    if (in_array($statusLower, $statusesEmAberto, true)) {
        $aberturaTs = strtotime($c['data_abertura'] ?? '');
        if ($aberturaTs && (time() - $aberturaTs) > 48 * 3600) {
            $abertos48hTotal++;
        }
    }
}

// 6) Dados para os gráficos
$contagemSetores = [];
$contagemLojas   = [];

foreach ($chamados as $c) {
    if (!empty($c['setor_destino_nome'])) {
        $contagemSetores[$c['setor_destino_nome']] =
            ($contagemSetores[$c['setor_destino_nome']] ?? 0) + 1;
    }

    if (!empty($c['loja_destino_nome'])) {
        $contagemLojas[$c['loja_destino_nome']] =
            ($contagemLojas[$c['loja_destino_nome']] ?? 0) + 1;
    }
}

arsort($contagemSetores);
arsort($contagemLojas);

$labelsSetores  = json_encode(array_keys($contagemSetores), JSON_UNESCAPED_UNICODE);
$valoresSetores = json_encode(array_values($contagemSetores), JSON_UNESCAPED_UNICODE);

$labelsLojas  = json_encode(array_keys($contagemLojas), JSON_UNESCAPED_UNICODE);
$valoresLojas = json_encode(array_values($contagemLojas), JSON_UNESCAPED_UNICODE);

// =========================
// CONTEÚDO
// =========================
ob_start();
?>
<link rel="stylesheet" href="/css/chamados_admin.css">

<div class="container">

  <h2>📞 Chamados por Setor/Loja</h2>
  <p>Acompanhe e visualize chamados direcionados aos setores ou lojas.</p>

  <!-- Gráficos -->
  <h3>📊 Chamados por Setor</h3>
  <canvas id="graficoSetores" style="max-height:300px; margin-bottom:40px;"></canvas>

  <h3>🏬 Chamados por Loja</h3>
  <canvas id="graficoLojas" style="max-height:300px; margin-bottom:40px;"></canvas>

  <?php if ($abertos48hTotal > 0): ?>
    <div class="alerta">
      ⚠️ Atenção: Existem <?= $abertos48hTotal ?> chamado(s) aberto(s) há mais de 48 horas!
    </div>
  <?php endif; ?>

  <!-- Filtros -->
  <form method="GET" style="margin-bottom:20px">

    <!-- DESTINO -->
    <label for="filtroDestino">Destino:</label>
    <select name="destino" id="filtroDestino">
        <option value="">Todos</option>

        <optgroup label="Setores">
        <?php
        $resSetores = $conn->query("SELECT nome FROM setores ORDER BY nome ASC");
        while ($setor = $resSetores->fetch_assoc()):
        ?>
            <option value="<?= $setor['nome'] ?>" <?= ($filtroDestino == $setor['nome']) ? 'selected' : '' ?>>
                Setor: <?= htmlspecialchars($setor['nome']) ?>
            </option>
        <?php endwhile; ?>
        </optgroup>

        <optgroup label="Lojas">
        <?php
        $resLojasDest = $conn->query("SELECT id, nome FROM lojas ORDER BY nome ASC");
        while ($ld = $resLojasDest->fetch_assoc()):
        ?>
            <option value="<?= $ld['id'] ?>" <?= ($filtroDestino == $ld['id']) ? 'selected' : '' ?>>
                Loja: <?= htmlspecialchars($ld['nome']) ?>
            </option>
        <?php endwhile; ?>
        </optgroup>
    </select>

    <!-- TEMPO ABERTO -->
    <label for="filtroTempo">Tempo aberto:</label>
    <select name="tempo" id="filtroTempo">
        <option value="">Todos</option>
        <option value="48" <?= ($filtroTempo == '48') ? 'selected' : '' ?>>Mais de 48 horas</option>
        <option value="72" <?= ($filtroTempo == '72') ? 'selected' : '' ?>>Mais de 72 horas</option>
    </select>

    <!-- BUSCA -->
    <label for="filtroBusca">Pesquisar chamado:</label>
    <input type="text" name="busca" id="filtroBusca"
           value="<?= htmlspecialchars($filtroBusca) ?>"
           placeholder="Código ou título">

    <button type="submit" class="btn">Filtrar</button>
    <button type="button" class="btn" onclick="window.location.href='chamados_admin.php'">
        🧹 Limpar filtros
    </button>
  </form>

  <!-- tabela -->
  <div class="tabela-container">
    <table>
      <thead>
        <tr>
          <th>Chamado</th>
          <th>Destino</th>
          <th>Status / Motivo</th>
          <th>Tempo aberto</th>
          <th>Solicitante</th>
          <th>Detalhes</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($chamados)): ?>
          <tr><td colspan="6" style="text-align:center;">Nenhum chamado encontrado.</td></tr>
        <?php else: ?>
          <?php foreach ($chamados as $c): ?>
            <?php
              $codigo          = $c['codigo_chamado'];
              $setor           = $c['setor_destino_nome'];
              $lojaDestino     = $c['loja_destino_nome'];
              $destino         = $setor ? "Setor: $setor" : ($lojaDestino ? "Loja: $lojaDestino" : '—');

              $aberturaTs      = strtotime($c['data_abertura']);
              $solicitanteNome = $c['solicitante_nome'];
              $lojaOrigem      = $c['loja_origem_nome'];
              $statusRaw       = trim($c['status']);
              $motivo          = trim($c['justificativa']);
              $statusMotivo    = $statusRaw . ($motivo ? " – $motivo" : '');

              $tempoAbertoSeg = time() - $aberturaTs;
              $dias    = floor($tempoAbertoSeg / 86400);
              $horas   = floor(($tempoAbertoSeg % 86400) / 3600);
              $minutos = floor(($tempoAbertoSeg % 3600) / 60);
              $tempoAbertoFmt = "{$dias}d {$horas}h {$minutos}m";

              $linhaAlerta = in_array(strtolower($statusRaw), $statusesEmAberto)
                             && $tempoAbertoSeg > 48*3600;
            ?>
            <tr class="<?= $linhaAlerta ? 'linha-alerta' : '' ?>">
              <td><?= htmlspecialchars($codigo) ?></td>
              <td><?= htmlspecialchars($destino) ?></td>
              <td><?= htmlspecialchars($statusMotivo) ?></td>
              <td><?= $tempoAbertoFmt ?></td>
              <td><?= htmlspecialchars($solicitanteNome) ?> (<?= htmlspecialchars($lojaOrigem) ?>)</td>
              <td>
                <button class="btn" type="button"
                  onclick="abrirModalDetalhesChamadoAdmin(<?= $c['id'] ?>)">
                  🔍
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Paginação -->
  <?php if ($totalPaginas > 1): ?>
    <div class="paginacao">
      <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
        <?php
          $link = "?pagina=$i";
          if ($filtroDestino) $link .= "&destino=$filtroDestino";
          if ($filtroTempo)   $link .= "&tempo=$filtroTempo";
          if ($filtroBusca)   $link .= "&busca=$filtroBusca";
        ?>
        <a href="<?= $link ?>" class="<?= ($i == $paginaAtual) ? 'ativo' : '' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

  <div class="botoes-acoes" style="margin:50px;">
    <a class="btn" href="../index.php">🏠 Voltar</a>
    <a class="btn" href="chamados_encerrados_admin.php">📁 Encerrados</a>
  </div>

</div>
<?php
$conteudo = ob_get_clean();

// =========================
// MODAIS
// =========================
ob_start();
?>
<div id="modalDetalhesChamado" class="modal">
  <div class="modal-content">
    <span class="modal-close" onclick="fecharModalDetalhesChamado()">×</span>
    <h3>🔍 Detalhes do chamado</h3>
    <div id="conteudoDetalhesChamado">Carregando...</div>
  </div>
</div>
<?php
$modais = ob_get_clean();

// =========================
// SCRIPTS
// =========================
ob_start();
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

<script>
window.labelsSetores  = <?= $labelsSetores ?>;
window.valoresSetores = <?= $valoresSetores ?>;

window.labelsLojas  = <?= $labelsLojas ?>;
window.valoresLojas = <?= $valoresLojas ?>;
</script>

<script src="/js/chamados_admin.js"></script>
<?php
$scripts = ob_get_clean();

include '../includes/layout.php';
