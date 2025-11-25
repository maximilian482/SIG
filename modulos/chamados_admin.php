<?php
session_start();
require_once '../dados/conexao.php';
date_default_timezone_set('America/Sao_Paulo');

$conn = conectar();

$usuarioId   = intval($_SESSION['funcionario_id'] ?? 0);

// Filtros
$filtroLoja    = $_GET['loja'] ?? '';
$filtroSetor   = $_GET['setor_destino'] ?? '';
$filtroStatus  = $_GET['status'] ?? '';
$filtroBusca   = $_GET['busca'] ?? '';

$paginaAtual   = max(1, intval($_GET['pagina'] ?? 1));
$porPagina     = 10;

// Parte comum da query (sem filtros)
$sqlFrom = "
  FROM chamados c
  LEFT JOIN funcionarios f ON f.id = c.solicitante_id
  LEFT JOIN lojas l ON l.id = c.loja_origem
  WHERE 1=1
    AND LOWER(TRIM(c.status)) <> 'encerrado'
";

// Montagem única de WHERE e parâmetros
$where  = '';
$params = [];
$types  = '';

// Filtro por loja (id)
if ($filtroLoja !== '') {
  $where   .= " AND c.loja_origem = ? ";
  $params[] = $filtroLoja;
  $types   .= 'i';
}

// Filtro por setor (texto exato)
if ($filtroSetor !== '') {
  $where   .= " AND c.setor_destino = ? ";
  $params[] = $filtroSetor;
  $types   .= 's';
}

// Filtro por status (normalizando)
if ($filtroStatus !== '') {
  $where   .= " AND LOWER(TRIM(c.status)) = LOWER(TRIM(?)) ";
  $params[] = $filtroStatus;
  $types   .= 's';
}

// Filtro de busca (código do chamado ou título)
if ($filtroBusca !== '') {
  $like     = "%".$filtroBusca."%";
  $where   .= " AND (c.codigo_chamado LIKE ? OR c.titulo LIKE ?) ";
  $params[] = $like;
  $params[] = $like;
  $types   .= 'ss';
}

// 1) Contar total (para paginação)
$sqlCount = "SELECT COUNT(*) AS total ".$sqlFrom.$where;
$stmtCount = $conn->prepare($sqlCount);
if (!empty($params)) $stmtCount->bind_param($types, ...$params);
$stmtCount->execute();
$totalRegistros = $stmtCount->get_result()->fetch_assoc()['total'] ?? 0;
$totalPaginas   = max(1, ceil($totalRegistros / $porPagina));

// 2) Buscar página atual
$offset   = ($paginaAtual - 1) * $porPagina;
$sqlPagina = "SELECT c.*, f.nome AS solicitante_nome, l.nome AS loja_nome ".$sqlFrom.$where." ORDER BY c.data_abertura ASC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sqlPagina);

// Acrescenta limit/offset nos parâmetros da página
$paramsPagina = $params;
$typesPagina  = $types . 'ii';
$paramsPagina[] = $porPagina;
$paramsPagina[] = $offset;

$stmt->bind_param($typesPagina, ...$paramsPagina);
$stmt->execute();
$chamados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3) Buscar todos os registros (sem paginação) para alerta global (>48h)
$sqlTodos = "SELECT c.id, c.status, c.data_abertura ".$sqlFrom.$where;
$stmtAll = $conn->prepare($sqlTodos);
if (!empty($params)) $stmtAll->bind_param($types, ...$params);
$stmtAll->execute();
$chamadosTodos = $stmtAll->get_result()->fetch_all(MYSQLI_ASSOC);

// 4) IDs com alerta >48h (apenas na página atual, para destacar linhas)
$alertaChamados = [];
foreach ($chamados as $c) {
  $statusLower = strtolower(trim($c['status'] ?? ''));
  if ($statusLower === 'aberto') {
    $aberturaTs = strtotime($c['data_abertura'] ?? '');
    if ($aberturaTs && (time() - $aberturaTs) > 48 * 3600) {
      $alertaChamados[] = $c['id'];
    }
  }
}

// 5) Total global de abertos >48h
$abertos48hTotal = 0;
foreach ($chamadosTodos as $c) {
  $statusLower = strtolower(trim($c['status'] ?? ''));
  if ($statusLower === 'aberto') {
    $aberturaTs = strtotime($c['data_abertura'] ?? '');
    if ($aberturaTs && (time() - $aberturaTs) > 48 * 3600) {
      $abertos48hTotal++;
    }
  }
}

// A partir daqui, renderize sua tabela usando $chamados, $alertaChamados, $totalPaginas e $paginaAtual
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Chamados por Setor</title>
  <link rel="stylesheet" href="../css/chamados.css">
  <style>
    .alerta { background:#f8d7da; color:#721c24; padding:10px; border:1px solid #f5c6cb; border-radius:5px; margin-bottom:20px; }
    .status-aberto { background:#e74c3c; color:#fff; padding:4px 8px; border-radius:4px; }
    .status-andamento { background:#f1c40f; color:#000; padding:4px 8px; border-radius:4px; }
    .linha-alerta { background:#f8d7da !important; color:#721c24 !important; }
    .btn { cursor:pointer; }
  </style>
</head>
<body>
<div class="container">

  <h2>📞 Chamados por Setor</h2>
  <p>Acompanhe e registre chamados direcionados aos setores.</p>

  <?php if ($abertos48hTotal > 0): ?>
  <div class="alerta">
    ⚠️ Atenção: Existem chamado(s) aberto(s) há mais de 48 horas!
  </div>
<?php endif; ?>
<form method="GET" style="margin-bottom:20px">

  <form method="GET" style="margin-bottom:20px">

  <!-- Filtro por Loja -->
  <label for="filtroLoja">Loja:</label>
  <select name="loja" id="filtroLoja">
    <option value="">Todas</option>
    <?php
    // Buscar lista de lojas no banco
    $resLojas = $conn->query("SELECT id, nome FROM lojas ORDER BY nome ASC");
    while ($loja = $resLojas->fetch_assoc()):
    ?>
      <option value="<?= htmlspecialchars($loja['id']) ?>"
        <?= (($_GET['loja'] ?? '') == $loja['id']) ? 'selected' : '' ?>>
        <?= htmlspecialchars($loja['nome']) ?>
      </option>
    <?php endwhile; ?>
  </select>

  <!-- Filtro por Setor -->
  <label for="filtroSetor">Setor destino:</label>
  <select name="setor_destino" id="filtroSetor">
    <option value="">Todos</option>
    <?php
    // Buscar lista de setores distintos dos chamados
    $resSetores = $conn->query("SELECT DISTINCT setor_destino FROM chamados ORDER BY setor_destino ASC");
    while ($setor = $resSetores->fetch_assoc()):
      $nomeSetor = $setor['setor_destino'];
    ?>
      <option value="<?= htmlspecialchars($nomeSetor) ?>"
        <?= (($_GET['setor_destino'] ?? '') == $nomeSetor) ? 'selected' : '' ?>>
        <?= htmlspecialchars($nomeSetor) ?>
      </option>
    <?php endwhile; ?>
  </select>

  <!-- Filtro de busca -->
  <label for="filtroBusca">Pesquisar chamado:</label>
  <input type="text" name="busca" id="filtroBusca"
         value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>"
         placeholder="Código ou título">

  <!-- Botões -->
  <button type="submit">Filtrar</button>
  <button type="button" onclick="window.location.href='<?= basename($_SERVER['PHP_SELF']) ?>'">
    Limpar filtros
  </button>
</form>




  <!-- tabela -->
  <div class="tabela-container">
   <table>
  <thead>
    <tr>
      <th>Chamado</th>
      <th>Setor destino</th>
      <th>Tempo aberto</th>
      <th>Solicitante</th>
      <th>Ação</th>
      <th>Detalhes</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($chamados)): ?>
      <tr><td colspan="6" style="text-align:center;">Nenhum chamado encontrado.</td></tr>
    <?php else: ?>
      <?php foreach ($chamados as $c): ?>
        <?php
          $codigo          = $c['codigo_chamado'] ?? '';   // usa o campo correto
          $setor           = $c['setor_destino'] ?? '';
          $abertura        = $c['data_abertura'] ?? '';
          $aberturaTs      = strtotime($abertura) ?: 0;
          $solicitanteNome = $c['solicitante_nome'] ?? '';
          $lojaNome        = $c['loja_nome'] ?? '';
          $statusRaw       = trim($c['status'] ?? '');
          $statusLower     = strtolower($statusRaw);

          // calcular tempo aberto
          $tempoAbertoSeg = $aberturaTs ? (time() - $aberturaTs) : 0;
          $dias   = floor($tempoAbertoSeg / 86400);
          $horas  = floor(($tempoAbertoSeg % 86400) / 3600);
          $minutos= floor(($tempoAbertoSeg % 3600) / 60);
          $tempoAbertoFmt = $dias."d ".$horas."h ".$minutos."m";

          // alerta se aberto > 48h
          $linhaAlerta = ($statusLower === 'aberto' && $tempoAbertoSeg > 48*3600);
        ?>
        <tr class="<?= $linhaAlerta ? 'linha-alerta' : '' ?>">
          <td><?= htmlspecialchars($codigo) ?></td>
          <td><?= htmlspecialchars($setor) ?></td>
          <td><?= $tempoAbertoFmt ?></td>
          <td><?= htmlspecialchars($solicitanteNome) ?> (<?= htmlspecialchars($lojaNome) ?>)</td>
          <td>
            <?php
              $idChamado = (int)($c['id'] ?? 0);
              $ehSolicitante = ($c['solicitante_id'] ?? 0) == ($_SESSION['funcionario_id'] ?? 0);

              // Defina quais status permitem resposta do solicitante
              $podeResponder = $ehSolicitante && in_array($statusLower, ['retornado','aguardando avaliacao']);
            ?>

            <?php if ($podeResponder && $idChamado > 0): ?>
              <button type="button" class="btn"
                onclick="abrirModalAvaliacao(<?= $idChamado ?>)"
                title="Responder avaliação do chamado <?= htmlspecialchars($codigo) ?>">
                Responder
              </button>
            <?php else: ?>
              <span>—</span>
            <?php endif; ?>
          </td>
          <td>
            <button class="btn" type="button"
              onclick="abrirModalDetalhes(this)"
              data-codigo="<?= htmlspecialchars($c['codigo_chamado'] ?? '') ?>"
              data-titulo="<?= htmlspecialchars($c['titulo'] ?? '') ?>"
              data-descricao="<?= htmlspecialchars($c['descricao'] ?? '') ?>"
              data-setor="<?= htmlspecialchars($c['setor_destino'] ?? '') ?>"
              data-loja="<?= htmlspecialchars($c['loja_nome'] ?? '') ?>"
              data-solicitante="<?= htmlspecialchars($c['solicitante_nome'] ?? '') ?>"
              data-status="<?= htmlspecialchars($c['status'] ?? '') ?>"
              data-abertura="<?= htmlspecialchars($c['data_abertura'] ?? '') ?>"
              data-responsavel="<?= htmlspecialchars($c['responsavel'] ?? '') ?>"
              data-assumido="<?= htmlspecialchars($c['data_assumido'] ?? '') ?>"

              data-solucao="<?= htmlspecialchars($c['solucao'] ?? '') ?>"
              data-data-avaliacao="<?= htmlspecialchars($c['data_avaliacao'] ?? '') ?>"
              data-data-solucao="<?= htmlspecialchars($c['data_solucao'] ?? '') ?>"
              data-avaliacao="<?= htmlspecialchars($c['avaliacao'] ?? '') ?>"
            >
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
  <?php if (!empty($totalPaginas) && $totalPaginas > 1): ?>
    <div class="paginacao">
      <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
        <?php
          $link = "?pagina=$i";
          if (!empty($filtroSetor)) $link .= "&setor_destino=" . urlencode($filtroSetor);
          if (!empty($filtroStatus)) $link .= "&status=" . urlencode($filtroStatus);
        ?>
        <a href="<?= $link ?>" class="<?= ($i == ($paginaAtual ?? 1)) ? 'ativo' : '' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

  <!-- botões -->
  <div class="botoes-acoes" style="margin:50px;">
    <a class="btn" href="../modulos/pendencias.php">🏠 Voltar</a>
    <button type="button" class="btn" onclick="abrirModalChamado()">➕ Novo</button>
    <a class="btn" href="chamados_encerrados_admin.php">📁 Encerrados</a>
  </div>

</div>

<!-- Modal de novo chamado -->
<div id="modalChamado" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999;">
  <div style="background:#fff; margin:5% auto; padding:20px; width:500px; border-radius:8px;">
    <h3>➕ Registrar chamado</h3>
    <form id="formChamado" onsubmit="enviarChamado(event)">
      
      <label><strong>Setor destino:</strong></label><br>
      <select name="setor_destino" required style="width:100%;">
        <option value="">Selecione o setor</option>
        <option value="Financeiro">Financeiro</option>
        <option value="RH">Recursos Humanos</option>
        <option value="TI">Tecnologia da Informação</option>
        <option value="Compras">Compras</option>
        <option value="Logística">Logística</option>
        <option value="Marketing">Marketing</option>
        <option value="Vendas">Vendas</option>
      </select><br><br>

      <label><strong>Título:</strong></label><br>
      <input type="text" name="titulo" required style="width:100%;"><br><br>

      <label><strong>Descrição:</strong></label><br>
      <textarea name="descricao" rows="4" required style="width:100%;"></textarea><br><br>

      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" onclick="fecharModalChamado()">Cancelar</button>
        <button type="submit">Confirmar</button>
      </div>
    </form>
  </div>
</div>


<!-- Modal de resposta (setor) -->
<div id="modalResposta" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999;">
  <div style="background:#fff; margin:5% auto; padding:20px; width:500px; border-radius:8px;">
    <h3>✍️ Responder chamado</h3>
    <form id="formResposta">
      <input type="hidden" name="id" id="respostaId">
      <div style="margin-bottom:10px;">
        <label><strong>Resposta / Solução:</strong></label><br>
        <textarea name="resposta" id="respostaTexto" rows="4" style="width:100%;" required></textarea>
      </div>
      <p>Deseja encerrar este chamado?</p>
      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" onclick="fecharModalResposta()">Cancelar</button>
        <button type="button" onclick="enviarRespostaComAcao('setor_encerrar')">✅ Sim, encerrar (aguardar avaliação)</button>
        <button type="button" onclick="enviarRespostaComAcao('setor_andamento')">🔄 Manter em andamento</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal de avaliação (solicitante) -->
<div id="modalAvaliacao" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999;">
  <div style="background:#fff; margin:5% auto; padding:20px; width:500px; border-radius:8px;">
    <h3>📝 Avaliação do chamado</h3>
    <form id="formAvaliacao" onsubmit="enviarAvaliacao(event)">
      <input type="hidden" name="id" id="avaliacaoId">

      <p>Você ficou satisfeito com a solução?</p>
      <div style="margin-bottom:10px;">
        <label>
          <input type="radio" name="avaliacao" value="Sim" onclick="toggleJustificativa(false)" required>
          👍 Fui atendido
        </label><br>
        <label>
          <input type="radio" name="avaliacao" value="Não" onclick="toggleJustificativa(true)">
          👎 Não fui atendido
        </label>
      </div>

      <div id="campoJustificativa" style="display:none; margin-bottom:10px;">
        <label><strong>Justificativa:</strong></label><br>
        <textarea name="justificativa" id="justificativaTexto" rows="4" style="width:100%;"></textarea>
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" onclick="fecharModalAvaliacao()">Cancelar</button>
        <button type="submit">Confirmar</button>
      </div>
    </form>
  </div>
</div>
<div id="modalDetalhes" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:999;">
  <div style="background:#fff; margin:3% auto; padding:20px; width:700px; max-width:95%; border-radius:8px;">
    <h3>📄 Detalhes do chamado</h3>
    <div id="detalhesConteudo" style="margin-top:10px;">
      <!-- Preenchido via JS -->
    </div>
    <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:16px;">
      <button type="button" class="btn" onclick="fecharModalDetalhes()">Fechar</button>
    </div>
  </div>
</div>

<script>
function abrirModalDetalhes(btn) {
  const data = btn.dataset;
  const wrap = document.getElementById('detalhesConteudo');
  if (!wrap) return;

  const campos = [
  {label:'Chamado',         valor:data.codigo},
  {label:'Loja origem',     valor:data.loja},
  {label:'Setor destino',   valor:data.setor},
  {label:'Título',          valor:data.titulo},
  {label:'Descrição',       valor:data.descricao},
  {label:'Solicitante',     valor:data.solicitante},
  {label:'Status',          valor:data.status},
  {label:'Responsável',     valor:data.responsavel},
  {label:'Data abertura',   valor:data.abertura},
  {label:'Data assumido',   valor:data.assumido},

  // Campos corrigidos com os nomes exatos
  {label:'Solução',         valor:data.solucao},
  {label:'Data solução',    valor:data.dataSolucao},
  {label:'Avaliação',       valor:data.avaliacao},
  {label:'Data avaliação',  valor:data.dataAvaliacao},
];


  let html = '<div style="display:grid; grid-template-columns: 1fr 2fr; gap:8px;">';
  campos.forEach(c => {
    html += `<div><strong>${c.label}:</strong></div><div>${c.valor ? escapeHtml(c.valor) : '—'}</div>`;
  });
  html += '</div>';

  wrap.innerHTML = html;
  document.getElementById('modalDetalhes').style.display = 'block';
}

function fecharModalDetalhes() {
  document.getElementById('modalDetalhes').style.display = 'none';
}

function escapeHtml(str) {
  return String(str)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#039;');
}
</script>

<script>
// ---------- Novo chamado ----------
function abrirModalChamado() {
  document.getElementById('modalChamado').style.display = 'block';
}
function fecharModalChamado() {
  document.getElementById('modalChamado').style.display = 'none';
}
function enviarChamado(event) {
  event.preventDefault();
  const form = event.target;
  const dados = new FormData(form);

  fetch('salvar_chamado.php', {
    method: 'POST',
    body: dados
  })
  .then(res => res.json())
  .then(data => {
    if (data.ok) {
      alert(data.mensagem + (data.codigo ? "\nCódigo: " + data.codigo : ""));
      fecharModalChamado();
      location.reload();
    } else {
      alert("❌ Erro: " + data.mensagem);
    }
  })
  .catch(err => {
    alert('❌ Erro ao salvar chamado.');
    console.error(err);
  });
}

// ---------- Resposta (setor) ----------
function abrirModalResposta(id) {
  document.getElementById('respostaId').value = id;
  document.getElementById('respostaTexto').value = '';
  document.getElementById('modalResposta').style.display = 'block';
}
function fecharModalResposta() {
  document.getElementById('modalResposta').style.display = 'none';
}
function enviarRespostaComAcao(acao) {
  const form = document.getElementById('formResposta');
  const dados = new FormData(form);
  const resposta = (dados.get('resposta') || '').trim();

  if (!resposta) {
    alert('❌ É necessário informar a solução/resposta.');
    return;
  }

  dados.set('acao', acao); // 'setor_encerrar' ou 'setor_andamento'

  fetch('salvar_avaliacao_chamado.php', {
    method: 'POST',
    body: dados
  })
  .then(res => res.json())
  .then(data => {
    if (data.ok) {
      alert(data.mensagem);
      fecharModalResposta();
      location.reload();
    } else {
      alert("❌ Erro: " + data.mensagem);
    }
  })
  .catch(err => {
    alert('❌ Erro ao enviar resposta.');
    console.error(err);
  });
}

// ---------- Avaliação (solicitante) ----------
function abrirModalAvaliacao(id) {
  document.getElementById('avaliacaoId').value = id;
  document.getElementById('justificativaTexto').value = '';
  document.getElementById('campoJustificativa').style.display = 'none';
  document.getElementById('modalAvaliacao').style.display = 'block';
}
function fecharModalAvaliacao() {
  document.getElementById('modalAvaliacao').style.display = 'none';
}
function toggleJustificativa(show) {
  document.getElementById('campoJustificativa').style.display = show ? 'block' : 'none';
}
function enviarAvaliacao(event) {
  event.preventDefault();
  const form = event.target;
  const dados = new FormData(form);
  const aval = dados.get('avaliacao');

  if (aval === 'Sim') {
    dados.set('acao', 'avaliacao_sim');
  } else if (aval === 'Não') {
    dados.set('acao', 'avaliacao_nao');
    const just = (dados.get('justificativa') || '').trim();
    if (!just) {
      alert('❌ Justificativa obrigatória quando não foi atendido.');
      return;
    }
  } else {
    alert('Selecione uma opção de avaliação.');
    return;
  }

  fetch('salvar_avaliacao_chamado.php', {
    method: 'POST',
    body: dados
  })
  .then(res => res.json())
  .then(data => {
    if (data.ok) {
      alert(data.mensagem);
      fecharModalAvaliacao();
      location.reload();
    } else {
      alert("❌ Erro: " + data.mensagem);
    }
  })
  .catch(err => {
    alert('❌ Erro ao salvar avaliação.');
    console.error(err);
  });
}
</script>


</body>
</html>
