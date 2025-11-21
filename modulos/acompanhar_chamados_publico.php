<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

if (!isset($_SESSION['usuario'])) {
  header('Location: ../login.php');
  exit;
}

$usuario      = $_SESSION['usuario'];
$cpf          = $_SESSION['cpf'] ?? '';
$lojaUsuario  = intval($_SESSION['loja'] ?? 0);
$nomeUsuario  = $_SESSION['nome'] ?? $usuario;
$cargo        = strtolower(trim($_SESSION['cargo'] ?? ''));
$usuarioId    = intval($_SESSION['funcionario_id'] ?? 0); // padronizado
$DEBUG        = isset($_GET['debug']) && $_GET['debug'] === '1';

// Buscar nome da loja
$nomeLoja = '—';
if ($lojaUsuario > 0) {
  $stmtLoja = $conn->prepare("SELECT nome FROM lojas WHERE id = ?");
  $stmtLoja->bind_param("i", $lojaUsuario);
  $stmtLoja->execute();
  $nomeLoja = $stmtLoja->get_result()->fetch_assoc()['nome'] ?? $lojaUsuario;
}

// Paginação e filtro
$paginaAtual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$filtroSetor = $_GET['setor'] ?? '';
$porPagina   = 10;
$inicio      = ($paginaAtual - 1) * $porPagina;

// WHERE dinâmico
$where  = "c.loja_origem = ? AND LOWER(c.status) != 'encerrado'";
$params = [$lojaUsuario];
$types  = "i";

if (!empty($filtroSetor)) {
  $where     .= " AND c.setor_destino = ?";
  $params[]   = $filtroSetor;
  $types     .= "s";
}

// Consulta com paginação
$query = "
  SELECT c.id,
         c.setor_destino,
         c.status,
         c.data_abertura,
         c.solicitante_id,
         l.nome AS nome_loja
  FROM chamados c
  JOIN lojas l ON l.id = c.loja_origem
  WHERE $where
  ORDER BY c.data_abertura DESC
  LIMIT ?, ?
";
$params[] = $inicio;
$params[] = $porPagina;
$types   .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$resultado = $stmt->get_result();
$chamados  = $resultado->fetch_all(MYSQLI_ASSOC);

// Total para paginação
$whereTotal   = "loja_origem = ? AND LOWER(status) != 'encerrado'";
$paramsTotal  = [$lojaUsuario];
$typesTotal   = "i";

if (!empty($filtroSetor)) {
  $whereTotal   .= " AND setor_destino = ?";
  $paramsTotal[] = $filtroSetor;
  $typesTotal   .= "s";
}

$stmtTotal = $conn->prepare("SELECT COUNT(*) AS total FROM chamados WHERE $whereTotal");
$stmtTotal->bind_param($typesTotal, ...$paramsTotal);
$stmtTotal->execute();
$totalChamados = intval($stmtTotal->get_result()->fetch_assoc()['total'] ?? 0);

// Utilitário de tempo
function tempoAbertoStr(?string $dataAbertura): string {
  if (!$dataAbertura) return '—';
  $ts = strtotime($dataAbertura);
  if (!$ts) return '—';
  $diff  = time() - $ts;
  $dias  = floor($diff / 86400);
  $horas = floor(($diff % 86400) / 3600);
  $min   = floor(($diff % 3600) / 60);
  return $dias > 0 ? "{$dias}d {$horas}h" : ($horas > 0 ? "{$horas}h {$min}m" : "{$min}m");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>📋 Meus Chamados</title>
  <link rel="stylesheet" href="../css/chamados.css">
</head>
<body>

<h2>📋 Chamados <?= htmlspecialchars($nomeLoja) ?></h2>
<p>Visualize os chamados abertos e acompanhe o andamento.</p>

<form method="GET" style="margin-bottom:20px;">
  <label for="setor">🔍 Filtrar por setor:</label>
  <select name="setor" id="setor" onchange="this.form.submit()">
    <option value="">— Todos os setores —</option>
    <option value="TI"         <?= (isset($_GET['setor']) && $_GET['setor'] === 'TI') ? 'selected' : '' ?>>TI</option>
    <option value="Manutencao" <?= (isset($_GET['setor']) && $_GET['setor'] === 'Manutencao') ? 'selected' : '' ?>>Manutenção</option>
    <option value="Supervisao" <?= (isset($_GET['setor']) && $_GET['setor'] === 'Supervisao') ? 'selected' : '' ?>>Supervisão</option>
    <option value="Financeiro" <?= (isset($_GET['setor']) && $_GET['setor'] === 'Financeiro') ? 'selected' : '' ?>>Financeiro</option>
    <option value="RH"         <?= (isset($_GET['setor']) && $_GET['setor'] === 'RH') ? 'selected' : '' ?>>RH</option>
    <option value="Compras"    <?= (isset($_GET['setor']) && $_GET['setor'] === 'Compras') ? 'selected' : '' ?>>Compras</option>
  </select>
  <?php if ($DEBUG): ?>
    <input type="hidden" name="debug" value="1">
  <?php endif; ?>
</form>

<?php if ($DEBUG): ?>
  <div style="background:#f8f9fa; border:1px solid #ddd; padding:10px; margin-bottom:10px;">
    <strong>Debug sessão:</strong> funcionario_id = <?= htmlspecialchars((string)$usuarioId) ?>
  </div>
<?php endif; ?>

<div class="tabela-container">
<table>
<tr>
  <th>ID</th>
  <th>Setor</th>
  <th>Status</th>
  <th>Tempo Aberto</th>
  <th>Ação</th>
  <th>Detalhes</th>
</tr>

<?php if (empty($chamados)): ?>
  <tr><td colspan="6" style="text-align:center;">Nenhum chamado encontrado.</td></tr>
<?php else: ?>
  <?php foreach ($chamados as $c): ?>
    <?php
      $statusRaw = strtolower(trim($c['status'] ?? ''));
      // Normalização simples de acentos para a comparação
      $statusNorm = str_replace(
        ['ç','ã','á','â','à','é','ê','í','ó','ô','ú'],
        ['c','a','a','a','a','e','e','i','o','o','u'],
        $statusRaw
      );

      $corStatus = match ($statusNorm) {
        'aberto'               => 'background:#fff3cd; color:#856404;',
        'em andamento'         => 'background:#cce5ff; color:#004085;',
        'aguardando avaliacao' => 'background:#ffeeba; color:#856404;',
        'reaberto'             => 'background:#f8d7da; color:#721c24;',
        default                => '',
      };

      $tempoAberto   = tempoAbertoStr($c['data_abertura'] ?? null);
      $solicitanteId = intval($c['solicitante_id'] ?? 0);
      $podeAvaliar   = (strpos($statusNorm, 'aguardando avaliacao') !== false && $solicitanteId === $usuarioId);
    ?>
    <tr>
      <td><?= htmlspecialchars($c['id']) ?></td>
      <td><?= htmlspecialchars($c['setor_destino'] ?? '') ?></td>
      <td style="<?= $corStatus ?> padding:4px; border-radius:4px;"><?= ucfirst($statusRaw) ?></td>
      <td><?= htmlspecialchars($tempoAberto) ?></td>
      <td>
        <?php if ($podeAvaliar): ?>
          <button type="button" onclick="abrirModalAvaliacaoChamado('<?= $c['id'] ?>')">
            📋 Avaliar atendimento
          </button>

        <?php elseif (strpos($statusNorm, 'aguardando avaliacao') !== false): ?>
          <span style="color:#999;">Aguardando avaliação pelo solicitante</span>
        <?php else: ?>
          -
        <?php endif; ?>

        <?php if ($DEBUG): ?>
          <div style="font-size:12px; color:#666; margin-top:6px;">
            [dbg] id=<?= htmlspecialchars((string)$c['id']) ?>,
            solicitante_id=<?= htmlspecialchars((string)$solicitanteId) ?>,
            funcionario_id=<?= htmlspecialchars((string)$usuarioId) ?>,
            statusRaw=<?= htmlspecialchars($statusRaw) ?>,
            statusNorm=<?= htmlspecialchars($statusNorm) ?>,
            podeAvaliar=<?= $podeAvaliar ? 'SIM' : 'NÃO' ?>
          </div>
        <?php endif; ?>
      </td>
      <td>
        <button onclick="abrirModalDetalhesChamado('<?= $c['id'] ?>')">🔍</button>
      </td>
    </tr>
  <?php endforeach; ?>
<?php endif; ?>
</table>
</div>

<?php
$totalPaginas = max(1, ceil($totalChamados / $porPagina));
if ($totalPaginas > 1): ?>
  <div class="paginacao">
    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
      <a href="?pagina=<?= $i ?>&setor=<?= urlencode($filtroSetor) ?><?= $DEBUG ? '&debug=1' : '' ?>"
         class="<?= $i == $paginaAtual ? 'ativo' : '' ?>">
         <?= $i ?>
      </a>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<div class="botoes-acoes" style="margin: 50px;">
  <a class="btn" href="../modulos/pendencias.php">🏠 Voltar</a>
  <a class="btn" href="abrir_chamado.php">➕ Novo Chamado</a>
  <a class="btn" href="chamados_encerrados.php">📁 Encerrados</a>
</div>

<!-- Modal de avaliação -->
<div id="modalAvaliacaoChamado" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999;">
  <div style="background:#fff; margin:5% auto; padding:20px; width:400px; border-radius:8px;">
    <h3>📋 Avaliar atendimento</h3>
    <form id="formAvaliacaoChamado" onsubmit="enviarAvaliacaoChamado(event)">
      <input type="hidden" id="avaliacaoChamadoId" name="id">
      
      <label for="avaliacaoChamadoNota"><strong>Você foi atendido?</strong></label><br>
      <select id="avaliacaoChamadoNota" name="avaliacao" required onchange="toggleJustificativa()" style="width:100%; margin-top:8px;">
        <option value="">Selecione</option>
        <option value="Sim">✅ Sim</option>
        <option value="Não">❌ Não</option>
      </select>

      <div id="justificativaContainer" style="display:none; margin-top:10px;">
        <label for="avaliacaoChamadoJustificativa"><strong>Descreva o motivo:</strong></label><br>
        <textarea id="avaliacaoChamadoJustificativa" name="justificativa" rows="3" style="width:100%;"></textarea>
      </div>

      <div style="margin-top:15px; text-align:right;">
        <button type="button" onclick="fecharModalAvaliacaoChamado()">Cancelar</button>
        <button type="submit">Confirmar</button>
      </div>
    </form>
  </div>
</div>


<!-- Modal detalhes do chamado -->
<div id="modalDetalhesChamado" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999;">
  <div style="background:#fff; margin:5% auto; padding:20px; width:500px; border-radius:8px;">
    <h3>🔍 Detalhes do chamado</h3>
    <div id="conteudoDetalhesChamado">Carregando...</div>
    <div style="text-align:right; margin-top:20px;">
      <button onclick="fecharModalDetalhesChamado()">Fechar</button>
    </div>
  </div>
</div>

<script>
/* --- Avaliação de chamado --- */
function enviarAvaliacaoChamado(event) {
  event.preventDefault();

  const id           = document.getElementById('avaliacaoChamadoId').value;
  const avaliacao    = document.getElementById('avaliacaoChamadoNota').value;
  const justificativa = document.getElementById('avaliacaoChamadoJustificativa').value;

  if (!id || !avaliacao) {
    alert('Preencha a avaliação.');
    return;
  }
  if (avaliacao === 'Não' && !justificativa.trim()) {
    alert('Informe a justificativa quando selecionar "Não".');
    return;
  }

  const params = new URLSearchParams({ id, avaliacao, justificativa });

  fetch('salvar_avaliacao_chamado.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: params.toString()
  })
  .then(r => r.text())
  .then(msg => {
    alert(msg || 'Avaliação enviada com sucesso!');
    fecharModalAvaliacaoChamado();
    location.reload();
  })
  .catch(() => alert('Erro ao enviar avaliação.'));
}

function abrirModalAvaliacaoChamado(id) {
  document.getElementById('avaliacaoChamadoId').value = id;
  document.getElementById('avaliacaoChamadoNota').value = '';
  document.getElementById('avaliacaoChamadoJustificativa').value = '';
  document.getElementById('justificativaContainer').style.display = 'none';
  document.getElementById('modalAvaliacaoChamado').style.display = 'block';
}

function fecharModalAvaliacaoChamado() {
  document.getElementById('modalAvaliacaoChamado').style.display = 'none';
}

function toggleJustificativa() {
  const nota = document.getElementById('avaliacaoChamadoNota').value;
  document.getElementById('justificativaContainer').style.display = (nota === 'Não') ? 'block' : 'none';
}

/* --- Detalhes do chamado --- */
function abrirModalDetalhesChamado(id) {
  const modal    = document.getElementById('modalDetalhesChamado');
  const conteudo = document.getElementById('conteudoDetalhesChamado');
  conteudo.innerHTML = 'Carregando...';

  fetch('detalhes_chamado.php?id=' + encodeURIComponent(id))
    .then(r => r.text())
    .then(html => conteudo.innerHTML = html)
    .catch(() => conteudo.innerHTML = '<p style="color:red;">Erro ao carregar detalhes.</p>');

  modal.style.display = 'block';
}

function fecharModalDetalhesChamado() {
  document.getElementById('modalDetalhesChamado').style.display = 'none';
}
</script>


</body>
</html>
