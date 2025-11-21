<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

// Carregar lojas do JSON (se ainda não estiverem no banco)
$lojas = [];
$resLojas = $conn->query("SELECT id, nome FROM lojas ORDER BY nome");
while ($row = $resLojas->fetch_assoc()) {
  $lojas[$row['id']] = ['nome' => $row['nome']];
}


// Filtros
$lojaSelecionada  = $_GET['loja'] ?? '';
$cargoSelecionado = $_GET['cargo'] ?? '';
$paginaAtual      = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$limite           = isset($_GET['limite']) ? max(10, intval($_GET['limite'])) : 10;
$busca            = trim($_GET['busca'] ?? '');
$inicio           = ($paginaAtual - 1) * $limite;

// Carregar todos os cargos disponíveis do banco
$cargosDisponiveis = [];
$res = $conn->query("SELECT nome_cargo FROM cargos ORDER BY nome_cargo");
while ($row = $res->fetch_assoc()) {
  $cargosDisponiveis[$row['nome_cargo']] = true;
}

// Função para montar filtros SQL
function montarFiltros(&$sql, &$params, &$types, $loja, $cargo, $busca) {
  $sql .= " WHERE f.desligamento IS NULL";

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

// Consulta principal
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
  $listaFiltrada[] = [
    'loja' => $f['loja_id'],
    'id' => $f['id'],
    'dados' => $f
  ];
}

// Consulta de contagem total
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

function tempoDeEmpresa($dataContratacao) {
  if (!$dataContratacao) return '—';
  $hoje = new DateTime();
  $inicio = new DateTime($dataContratacao);
  $intervalo = $inicio->diff($hoje);
  $anos = $intervalo->y;
  $meses = $intervalo->m;
  $texto = '';
  if ($anos > 0) $texto .= $anos . ' ano' . ($anos > 1 ? 's' : '');
  if ($meses > 0) {
    if ($texto) $texto .= ' e ';
    $texto .= $meses . ' mes' . ($meses > 1 ? 'es' : '');
  }
  return $texto ?: 'Menos de 1 mes';
}

$sucesso = isset($_GET['sucesso']) && $_GET['sucesso'] == 1;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Funcionários Ativos</title>
  <link rel="stylesheet" href="../css/funcionarios.css">
</head>
<body>
<?php if (isset($_SESSION['alerta'])): ?>
  <div class="<?= strpos($_SESSION['alerta'], '✅') !== false ? 'alert-sucesso' : 'alert-erro' ?>">
    <?= $_SESSION['alerta'] ?>
  </div>
  <?php unset($_SESSION['alerta']); ?>
<?php endif; ?>


<?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == 1): ?>
  <div class="alert-sucesso">
    💾 Funcionário cadastrado com sucesso!
  </div>
<?php endif; ?>



<h2>👥 Funcionários</h2>

<form method="GET" style="margin-bottom: 20px;">
  <label>Loja:</label>
  <select name="loja" onchange="this.form.submit()">
    <option value="">— Todas —</option>
    <?php foreach ($lojas as $id => $loja): ?>
      <option value="<?= $id ?>" <?= (string)$id === (string)$lojaSelecionada ? 'selected' : '' ?>>
        <?= htmlspecialchars($loja['nome'] ?? $id) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <label style="margin-left:10px;">Cargo:</label>
  <select name="cargo" onchange="this.form.submit()">
    <option value="">— Todos —</option>
    <?php foreach (array_keys($cargosDisponiveis) as $cargo): ?>
      <option value="<?= $cargo ?>" <?= $cargo === $cargoSelecionado ? 'selected' : '' ?>><?= $cargo ?></option>
    <?php endforeach; ?>
  </select>

  <label style="margin-left:10px;">Pesquisar:</label>
  <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Nome ou código" style="padding:4px;">
  <button type="submit" class="btn-filtro">🔍</button>
  <a href="funcionarios.php" class="btn-filtro">🧹</a>
</form>

<?php
if ($lojaSelecionada && !$cargoSelecionado) {
  $nomeLoja = '';
  foreach ($listaPaginada as $item) {
    if ($item['loja'] == $lojaSelecionada) {
      $nomeLoja = $item['dados']['nome_loja'] ?? $lojaSelecionada;
      break;
    }
  }
  echo "<p><strong>👥 Funcionários ativos na loja <em>$nomeLoja</em>:</strong> $totalFiltrados</p>";
}

if ($cargoSelecionado && !$lojaSelecionada) {
  echo "<p><strong>👥 Funcionários ativos no cargo <em>$cargoSelecionado</em>:</strong> $totalFiltrados</p>";
}

if ($lojaSelecionada && $cargoSelecionado) {
  $nomeLoja = '';
  foreach ($listaPaginada as $item) {
    if ($item['loja'] == $lojaSelecionada) {
      $nomeLoja = $item['dados']['nome_loja'] ?? $lojaSelecionada;
      break;
    }
  }
  echo "<p><strong>👥 Funcionários ativos na loja <em>$nomeLoja</em> com cargo <em>$cargoSelecionado</em>:</strong> $totalFiltrados</p>";
}

if ($busca !== '') {
  echo "<p><strong>🔍 Resultados para:</strong> <em>" . htmlspecialchars($busca) . "</em> — $totalFiltrados encontrado(s)</p>";
}

if ($lojaSelecionada === '' && $cargoSelecionado === '') {
  echo "<p><strong>👥 Total de funcionários ativos:</strong> $totalFiltrados</p>";
}
?>

<table>
  <tr>
    <th>Cód</th>
    <th>Cód Vetor</th>
    <th>CC</th>
    <th>Nome</th>
    <th>Endereço</th>
    <th>CPF</th>
    <th>Cargo</th>
    <th>Loja</th>
    <th>Contratação</th>
    <th>Tempo de empresa</th>
    <th>Nascimento</th>
    <th>Telefone</th>
    <th>Ações</th>
  </tr>

  <?php
  $codigoGlobal = $inicio + 1;
  foreach ($listaPaginada as $item):
    $f    = $item['dados'];
    $loja = $item['loja'];
    $id   = $item['id'];
    $codigoManual = $f['codigo'] ?? '—';
  ?>
    <tr>
      <td><?= $codigoGlobal++ ?></td>
      <td><?= htmlspecialchars($codigoManual) ?></td>
      <td><?= htmlspecialchars($f['cc'] ?? '—') ?></td>
      <td><?= htmlspecialchars($f['nome']) ?></td>
      <td><?= htmlspecialchars($f['endereco'] ?? '—') ?></td>
      <td><?= htmlspecialchars($f['cpf'] ?? '—') ?></td>
      <td><?= htmlspecialchars($f['nome_cargo'] ?? '—') ?></td>
      <td><?= htmlspecialchars($f['nome_loja'] ?? '—') ?></td>
      <td><?= !empty($f['contratacao']) ? date('d/m/Y', strtotime($f['contratacao'])) : '—' ?></td>
      <td><?= tempoDeEmpresa($f['contratacao'] ?? '') ?></td>
      <td><?= !empty($f['nascimento']) ? date('d/m/Y', strtotime($f['nascimento'])) : '—' ?></td>
      <td><?= htmlspecialchars($f['telefone'] ?? '—') ?></td>
      <td>
        <a href="editar_funcionario.php?loja=<?= urlencode($loja) ?>&id=<?= $id ?>">✏️</a> |
        <button type="button" onclick="abrirModalInativar('<?= htmlspecialchars($f['nome']) ?>', '<?= $loja ?>', '<?= $id ?>')">🗑️</button>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<?php if ($totalPaginas > 1): ?>
  <div style="margin-top:30px; display:flex; justify-content:center; align-items:center; gap:15px;">
    <div class="paginacao">
      <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
        <?php
          $params = http_build_query([
            'loja' => $lojaSelecionada,
            'cargo' => $cargoSelecionado,
            'limite' => $limite,
            'pagina' => $i,
            'busca' => $busca
          ]);
          $estilo = $i == $paginaAtual ? 'font-weight:bold; text-decoration:underline;' : '';
        ?>
        <a href="?<?= $params ?>" style="margin:0 5px; <?= $estilo ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>

    <form method="GET" style="margin-left:10px;">
      <input type="hidden" name="loja" value="<?= htmlspecialchars($lojaSelecionada) ?>">
      <input type="hidden" name="cargo" value="<?= htmlspecialchars($cargoSelecionado) ?>">
      <input type="hidden" name="busca" value="<?= htmlspecialchars($busca) ?>">
      <input type="hidden" name="pagina" value="1">
      <select name="limite" onchange="this.form.submit()" style="padding:4px;">
        <?php foreach ([10, 20, 30, 50, 100] as $opcao): ?>
          <option value="<?= $opcao ?>" <?= $opcao == $limite ? 'selected' : '' ?>><?= $opcao ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
<?php endif; ?>

<br>
<a class="btn" href="/modulos/gestao.php" style="margin-top:20px;">🏠 Voltar</a>
<a class="btn" href="adicionar_funcionario.php">➕</a>
<a class="btn" href="gestao_funcionarios.php" style="margin-left:10px;">📥 Gestao Funcionários</a>
<a class="btn" href="funcionarios_inativos.php" style="margin-left:10px;">🗂️ Inativos</a>

<!-- Modal de inativação -->
<div id="modalInativar" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
  <div style="background:#fff; margin:10% auto; padding:20px; width:400px; border-radius:8px;">
    <h3>🗑️ Confirmar inativação</h3>
    <p id="modalTextoInativar"></p>
    <form method="POST" action="salvar_inativacao.php" onsubmit="return confirmarInativacao()">
      <input type="hidden" name="loja" id="modalLojaInativar">
      <input type="hidden" name="id" id="modalIdInativar">
      <input type="hidden" name="nome" id="modalNomeInativar">

      <label>Data de desligamento:</label><br>
      <input type="date" name="desligamento" id="modalDataDesligamento" required><br><br>

      <button type="submit">Confirmar</button>
      <button type="button" onclick="fecharModalInativar()">Cancelar</button>
    </form>
  </div>
</div>

<script>
function abrirModalInativar(nome, loja, id) {
  document.getElementById('modalLojaInativar').value = loja;
  document.getElementById('modalIdInativar').value = id;
  document.getElementById('modalNomeInativar').value = nome;
  document.getElementById('modalTextoInativar').innerHTML =
    `Tem certeza que deseja inativar o funcionário <strong>${nome}</strong>?`;
  document.getElementById('modalInativar').style.display = 'block';
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

  return confirm(`Funcionário "${nome}" será inativado com data de desligamento ${data}. Deseja continuar?`);
}
</script>



</body>
</html>
