<?php
session_start();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';
require_once ROOT_PATH . '/dados/conexao.php';

$conn = conectar();


// ===============================
// CONFIGURAÇÕES DO LAYOUT
// ===============================
$titulo = "Funcionários Ativos";
$cssExtra = "/css/funcionarios.css"; // CSS específico desta página

// ===============================
// CARREGAR LOJAS
// ===============================
$lojas = [];
$resLojas = $conn->query("SELECT id, nome FROM lojas ORDER BY nome");
while ($row = $resLojas->fetch_assoc()) {
    $lojas[$row['id']] = ['nome' => $row['nome']];
}

// ===============================
// FILTROS
// ===============================
$lojaSelecionada  = $_GET['loja'] ?? '';
$cargoSelecionado = $_GET['cargo'] ?? '';
$paginaAtual      = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$limite           = isset($_GET['limite']) ? max(10, intval($_GET['limite'])) : 10;
$busca            = trim($_GET['busca'] ?? '');
$inicio           = ($paginaAtual - 1) * $limite;

// ===============================
// FUNÇÃO PARA MONTAR LINKS DE PAGINAÇÃO
// ===============================
function montarLinkPagina($pagina) {
    $params = $_GET;
    $params['pagina'] = $pagina;

    if (!isset($params['limite'])) {
        global $limite;
        $params['limite'] = $limite;
    }

    return '?' . http_build_query($params);
}

// ===============================
// CARREGAR CARGOS
// ===============================
$cargosDisponiveis = [];
$res = $conn->query("SELECT nome_cargo FROM cargos ORDER BY nome_cargo");
while ($row = $res->fetch_assoc()) {
    $cargosDisponiveis[$row['nome_cargo']] = true;
}

// ===============================
// FUNÇÃO DE FILTROS SQL
// ===============================
function montarFiltros(&$sql, &$params, &$types, $loja, $cargo, $busca) {
    $sql .= " WHERE f.desligamento IS NULL AND f.eh_funcionario = 1";

    if ($loja !== '') {
        $sql .= " AND f.loja_id = ?";
        $params[] = $loja;
        $types .= 'i';
    }

    if ($cargo !== '') {
        $sql .= " AND c.nome_cargo = ?";
        $params[] = $cargo;
        $types .= 's';
    }

    if ($busca !== '') {
        $sql .= " AND (LOWER(f.nome) LIKE ? OR f.codigo LIKE ?)";
        $buscaLike = '%' . strtolower($busca) . '%';
        $params[] = $buscaLike;
        $params[] = $buscaLike;
        $types .= 'ss';
    }
}

// ===============================
// CONSULTA PRINCIPAL
// ===============================
$sql = "
    SELECT f.*, l.nome AS nome_loja, c.nome_cargo AS nome_cargo
    FROM funcionarios f
    LEFT JOIN lojas l ON f.loja_id = l.id
    LEFT JOIN cargos c ON f.cargo_id = c.id
";

$params = [];
$types  = '';
montarFiltros($sql, $params, $types, $lojaSelecionada, $cargoSelecionado, $busca);

$sql .= " ORDER BY f.nome ASC LIMIT ?, ?";
$params[] = $inicio;
$params[] = $limite;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$listaFiltrada = [];
while ($f = $result->fetch_assoc()) {

    // Nome reduzido
    $partes = explode(' ', trim($f['nome']));
    $primeiro = $partes[0] ?? '';
    $ultimo   = $partes[count($partes)-1] ?? '';
    $f['nome_reduzido'] = $primeiro . ' ' . $ultimo;

    $listaFiltrada[] = [
        'loja'  => $f['loja_id'],
        'id'    => $f['id'],
        'dados' => $f
    ];
}

// ===============================
// CONTAGEM TOTAL
// ===============================
$sqlTotal = "
    SELECT COUNT(*) AS total
    FROM funcionarios f
    LEFT JOIN cargos c ON f.cargo_id = c.id
";

$paramsTotal = [];
$typesTotal  = '';
montarFiltros($sqlTotal, $paramsTotal, $typesTotal, $lojaSelecionada, $cargoSelecionado, $busca);

if (!empty($typesTotal)) {
    $stmtTotal = $conn->prepare($sqlTotal);
    $stmtTotal->bind_param($typesTotal, ...$paramsTotal);
    $stmtTotal->execute();
    $resultTotal = $stmtTotal->get_result();
} else {
    $resultTotal = $conn->query($sqlTotal);
}

$totalFiltrados = $resultTotal->fetch_assoc()['total'];
$totalPaginas   = ceil($totalFiltrados / $limite);
$listaPaginada  = $listaFiltrada;

// ===============================
// INICIAR CAPTURA DO HTML
// ===============================
ob_start();
?>


<h2>👥 Funcionários</h2>

<!-- ===============================
     FILTROS
=============================== -->
<div class="filtro-container">
  <form method="GET" class="filtro-form">

    <div class="filtro-grupo">
      <label>Loja</label>
      <select name="loja" onchange="this.form.submit()">
        <option value="">Todas</option>
        <?php foreach ($lojas as $id => $loja): ?>
          <option value="<?= $id ?>" <?= (string)$id === (string)$lojaSelecionada ? 'selected' : '' ?>>
            <?= htmlspecialchars($loja['nome']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="filtro-grupo">
      <label>Cargo</label>
      <select name="cargo" onchange="this.form.submit()">
        <option value="">Todos</option>
        <?php foreach (array_keys($cargosDisponiveis) as $cargo): ?>
          <option value="<?= $cargo ?>" <?= $cargo === $cargoSelecionado ? 'selected' : '' ?>>
            <?= $cargo ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="filtro-grupo">
      <label>Pesquisar</label>
      <div class="filtro-pesquisa">
        <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Nome ou código">
        <button type="submit" class="btn-small">🔍</button>
      </div>
    </div>

    <a href="funcionarios.php" class="btn-secondary btn-limpar">Limpar</a>

  </form>

  <div class="contador">
    <?= $totalFiltrados ?> funcionário(s) encontrado(s)
  </div>
</div>


<!-- ===============================
     TABELA
=============================== -->
<div class="card">
<table class="tabela-funcionarios">
  <tr>
    <th>Cód Vetor</th>
    <th>Nome</th>
    <th>Cargo</th>
    <th>Loja</th>
    <th>Detalhes</th>
  </tr>

  <?php foreach ($listaPaginada as $item):
    $f    = $item['dados'];
    $id   = $item['id'];
  ?>
    <tr>
      <td><?= htmlspecialchars($f['codigo'] ?? '—') ?></td>
      <td><?= htmlspecialchars($f['nome_reduzido']) ?></td>
      <td><?= htmlspecialchars($f['nome_cargo'] ?? '—') ?></td>
      <td><?= htmlspecialchars($f['nome_loja'] ?? '—') ?></td>
      <td><button class="btn-small" onclick="abrirDetalhesFuncionario(<?= $id ?>)">🔍</button></td>
    </tr>
  <?php endforeach; ?>
</table>
</div>

<!-- ===============================
     PAGINAÇÃO
=============================== -->
<?php if ($totalPaginas > 1): ?>
<div class="paginacao-container card">

    <div class="paginacao">
        <span>Página <?= $paginaAtual ?> de <?= $totalPaginas ?></span>

        <?php if ($paginaAtual > 1): ?>
            <a href="<?= montarLinkPagina($paginaAtual - 1) ?>">Anterior</a>
        <?php else: ?>
            <span class="desabilitado">Anterior</span>
        <?php endif; ?>

        <?php
        $range = 2;

        if ($paginaAtual > 1 + $range) {
            echo '<a href="'.montarLinkPagina(1).'">1</a>';
            if ($paginaAtual > 2 + $range) echo '<span class="reticencias">...</span>';
        }

        for ($i = max(1, $paginaAtual - $range); $i <= min($totalPaginas, $paginaAtual + $range); $i++) {
            if ($i == $paginaAtual) echo '<span class="pagina-atual">'.$i.'</span>';
            else echo '<a href="'.montarLinkPagina($i).'">'.$i.'</a>';
        }

        if ($paginaAtual < $totalPaginas - $range) {
            if ($paginaAtual < $totalPaginas - ($range + 1)) echo '<span class="reticencias">...</span>';
            echo '<a href="'.montarLinkPagina($totalPaginas).'">'.$totalPaginas.'</a>';
        }
        ?>

        <?php if ($paginaAtual < $totalPaginas): ?>
            <a href="<?= montarLinkPagina($paginaAtual + 1) ?>">Próxima</a>
        <?php else: ?>
            <span class="desabilitado">Próxima</span>
        <?php endif; ?>
    

    <form method="GET" class="itens-por-pagina-form">
        <?php foreach ($_GET as $k => $v):
            if ($k !== 'limite' && $k !== 'pagina'): ?>
                <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
        <?php endif; endforeach; ?>

        <label>Itens por página:</label>
        <select name="limite" onchange="this.form.submit()">
            <option value="10"  <?= $limite == 10  ? 'selected' : '' ?>>10</option>
            <option value="20"  <?= $limite == 20  ? 'selected' : '' ?>>20</option>
            <option value="50"  <?= $limite == 50  ? 'selected' : '' ?>>50</option>
            <option value="100" <?= $limite == 100 ? 'selected' : '' ?>>100</option>
        </select>
    </form>
</div>
</div>
<?php endif; ?>

<br>
<div class="acoes-final">
  <a class="btn" href="/modulos/funcionarios_menu.php">🏠 Voltar</a>
  <a class="btn" href="funcionarios_adicionar.php">➕</a>
  <a class="btn" href="funcionarios_inativos.php">🗂️ Inativos</a>
  <a href="funcionarios_gerar_hc.php" class="btn btn-success">📊 GERAR HC</a>

</div>

<!-- ===============================
     MODAL INATIVAÇÃO
=============================== -->
<div id="modalInativar" class="modal">
  <div class="modal-conteudo">
    <h3>🗑️ Confirmar inativação</h3>
    <p id="modalTextoInativar"></p>

    <form method="POST" action="funcionarios_salvar_inativacao.php" onsubmit="return confirmarInativacao()">
      <input type="hidden" name="loja" id="modalLojaInativar">
      <input type="hidden" name="id" id="modalIdInativar">
      <input type="hidden" name="nome" id="modalNomeInativar">

      <label>Data de desligamento:</label>
      <input type="date" name="desligamento" id="modalDataDesligamento" required>

      <div class="modal-botoes">
        <button type="submit" class="btn">Confirmar</button>
        <button type="button" class="btn-secondary" onclick="fecharModalInativar()">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- ===============================
     MODAL DETALHES
=============================== -->
<div id="modalDetalhes" class="modal">
  <div class="modal-conteudo">
    <h3>🔍 Detalhes do Funcionário</h3>
    <div id="conteudoDetalhes">Carregando...</div>

    <div class="modal-botoes">
      <button class="btn-secondary" onclick="fecharModalDetalhes()">Fechar</button>
    </div>
  </div>
</div>

<script>
function abrirDetalhesFuncionario(id) {
  const modal = document.getElementById('modalDetalhes');
  const conteudo = document.getElementById('conteudoDetalhes');

  conteudo.innerHTML = "Carregando...";

  fetch("funcionarios_detalhes.php?id=" + id)
    .then(r => r.text())
    .then(html => {
      conteudo.innerHTML = html;
      modal.style.display = "flex";
    })
    .catch(() => {
      conteudo.innerHTML = "Erro ao carregar detalhes.";
    });
}

function fecharModalDetalhes() {
  document.getElementById('modalDetalhes').style.display = "none";
}

function abrirModalInativar(nome, loja, id) {
  fecharModalDetalhes();
  document.getElementById('modalLojaInativar').value = loja;
  document.getElementById('modalIdInativar').value = id;
  document.getElementById('modalNomeInativar').value = nome;
  document.getElementById('modalTextoInativar').innerHTML =
    `Tem certeza que deseja inativar o funcionário <strong>${nome}</strong>?`;
  document.getElementById('modalInativar').style.display = 'flex';
}

function fecharModalInativar() {
  document.getElementById('modalInativar').style.display = 'none';
}

function confirmarInativacao() {
  const nome = document.getElementById('modalNomeInativar').value;
  const data = document.getElementById('modalDataDesligamento').value;

  if (!data) {
    alert("Por favor, selecione a data de desligamento.");
    return false;
  }

    // Converte aaaa-mm-dd → dd/mm/aaaa
    const partes = data.split("-");
    const dataBR = `${partes[2]}/${partes[1]}/${partes[0]}`;

    return confirm(`Funcionário "${nome}" será inativado com data de desligamento ${dataBR}. Deseja continuar?`);
    }
</script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . "/includes/layout.php";
