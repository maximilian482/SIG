<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

// Filtros
$lojaSelecionada  = $_GET['loja'] ?? '';
$cargoSelecionado = $_GET['cargo'] ?? '';
$paginaAtual      = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$limite           = isset($_GET['limite']) ? max(10, intval($_GET['limite'])) : 10;
$busca            = trim($_GET['busca'] ?? '');
$inicio           = ($paginaAtual - 1) * $limite;

// Carregar lojas
$lojas = [];
$resLojas = $conn->query("SELECT id, nome FROM lojas ORDER BY nome");
while ($row = $resLojas->fetch_assoc()) {
  $lojas[$row['id']] = $row['nome'];
}

// Carregar cargos (com ID e nome)
$cargos = [];
$resCargos = $conn->query("SELECT id, nome_cargo FROM cargos ORDER BY nome_cargo");
while ($row = $resCargos->fetch_assoc()) {
  $cargos[$row['id']] = $row['nome_cargo'];
}

// Função para montar filtros SQL
function montarFiltros(&$sql, &$params, &$types, $loja, $cargo, $busca) {
  $sql .= " WHERE f.desligamento IS NOT NULL";

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
$sql .= " ORDER BY f.desligamento DESC LIMIT ?, ?";
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

function tempoDeEmpresa($inicio, $fim) {
  if (!$inicio || !$fim) return '—';
  $inicio = new DateTime($inicio);
  $fim    = new DateTime($fim);
  $intervalo = $inicio->diff($fim);
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
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Funcionários Inativos</title>
  <link rel="stylesheet" href="../css/funcionarios.css">
  <style>
    .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); }
    .modal-content { background:#fff; margin:5% auto; padding:20px; border-radius:8px; width:90%; max-width:500px; }
    .close { float:right; font-size:24px; cursor:pointer; }
    .alert-sucesso { background:#d4edda; color:#155724; padding:12px; border-radius:6px; margin-bottom:20px; font-weight:bold; }
    .alert-erro { background:#f8d7da; color:#721c24; padding:12px; border-radius:6px; margin-bottom:20px; font-weight:bold; }
  </style>
</head>
<body>

<h2>🚫 Funcionários Inativos</h2>

<?php if (isset($_SESSION['alerta'])): ?>
  <div class="<?= strpos($_SESSION['alerta'], '✅') !== false ? 'alert-sucesso' : 'alert-erro' ?>">
    <?= $_SESSION['alerta'] ?>
  </div>
  <?php unset($_SESSION['alerta']); ?>
<?php endif; ?>

<form method="GET" style="margin-bottom: 20px;">
  <label>Loja:</label>
  <select name="loja" onchange="this.form.submit()">
    <option value="">— Todas —</option>
    <?php foreach ($lojas as $id => $nome): ?>
      <option value="<?= $id ?>" <?= (string)$id === (string)$lojaSelecionada ? 'selected' : '' ?>><?= htmlspecialchars($nome) ?></option>
    <?php endforeach; ?>
  </select>

  <label style="margin-left:20px;">Cargo:</label>
  <select name="cargo" onchange="this.form.submit()">
    <option value="">— Todos —</option>
    <?php foreach ($cargos as $idCargo => $nomeCargo): ?>
      <option value="<?= $nomeCargo ?>" <?= $nomeCargo === $cargoSelecionado ? 'selected' : '' ?>><?= $nomeCargo ?></option>
    <?php endforeach; ?>
  </select>

  <label style="margin-left:20px;">Pesquisar:</label>
  <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Nome ou código" style="padding:4px;">
  <button type="submit" class="btn-filtro">🔍</button>
  <a href="funcionarios_inativos.php" class="btn-filtro" style="margin-left:10px;">🧹</a>
</form>

<p><strong>Total de inativos encontrados:</strong> <?= $totalFiltrados ?></p>

<table>
  <tr>
    <th>Cód</th>
    <th>Cód Vetor</th>
    <th>CC </th>
    <th>Nome</th>
    <th>Cargo</th>
    <th>Loja</th>
    <th>Contratação</th>
    <th>Desligamento</th>
    <th>Tempo de empresa</th>
    <th>Telefone</th>
    <th>Ações</th>
  </tr>

<?php
$codigoGlobal = $inicio + 1;
foreach ($listaPaginada as $item):
  $f = $item['dados'];
?>
  <tr>
    <td><?= $codigoGlobal++ ?></td>
    <td><?= htmlspecialchars($f['codigo'] ?? '—') ?></td>
    <td><?= htmlspecialchars($f['cc'] ?? '—') ?></td>
    <td><?= htmlspecialchars($f['nome']) ?></td>
    <td><?= htmlspecialchars($f['nome_cargo'] ?? '—') ?></td>
    <td><?= htmlspecialchars($f['nome_loja'] ?? '—') ?></td>
    <td><?= htmlspecialchars($f['contratacao'] ?? '—') ?></td>
    <td><?= htmlspecialchars($f['desligamento'] ?? '—') ?></td>
    <td><?= tempoDeEmpresa($f['contratacao'], $f['desligamento']) ?></td>
    <td><?= htmlspecialchars($f['telefone'] ?? '—') ?></td>
    <td>
      <button type="button" class="btn-filtro"
              onclick='abrirModalReativacao(<?= json_encode($f, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>)'>
        ♻️ Reativar
      </button>
    </td>
  </tr>
<?php endforeach; ?>
</table>

<a href="funcionarios.php" class="btn-filtro" style="margin-top:20px; display:inline-block;">🔙 Voltar para ativos</a>

<!-- Modal de reativação -->
<div id="modalReativacao" class="modal">
  <div class="modal-content">
    <span class="close" onclick="fecharModal()">&times;</span>
    <h3>♻️ Reativar Funcionário</h3>
    <form method="POST" action="salvar_reativacao.php" id="formReativacao">
      <input type="hidden" name="loja_original" id="loja_original">
      <input type="hidden" name="id" id="id">

      <label>Nome:</label><br>
      <input type="text" name="nome" id="nome" required><br><br>

      <label>CPF:</label><br>
      <input type="text" name="cpf" id="cpf" readonly><br><br>

      <label>Código Manual:</label><br>
      <input type="text" name="codigo" id="codigo" required><br><br>

      <label>Código CC:</label><br>
      <input type="text" name="cc" id="cc" required><br><br>

      <label>Cargo:</label><br>
      <select name="cargo_id" id="cargo_id" required>
        <?php foreach ($cargos as $idCargo => $nomeCargo): ?>
          <option value="<?= $idCargo ?>"><?= htmlspecialchars($nomeCargo) ?></option>
        <?php endforeach; ?>
      </select><br><br>

      <label>Loja:</label><br>
      <select name="loja_id" id="loja_id" required>
        <?php foreach ($lojas as $idLoja => $nomeLoja): ?>
          <option value="<?= $idLoja ?>"><?= htmlspecialchars($nomeLoja) ?></option>
        <?php endforeach; ?>
      </select><br><br>

      <label>Nova data de contratação:</label><br>
      <input type="date" name="contratacao" id="contratacao" required><br><br>

      <label>Telefone:</label><br>
      <input type="text" name="telefone" id="telefone"><br><br>

      <label>Email:</label><br>
      <input type="email" name="email" id="email"><br><br>

      <label>Endereço:</label><br>
      <input type="text" name="endereco" id="endereco"><br><br>

      <label>Aniversário:</label><br>
      <input type="date" name="aniversario" id="aniversario"><br><br>

      <button type="submit" class="btn-filtro">✅ Confirmar reativação</button>
      <button type="button" class="btn-filtro" onclick="fecharModal()">❌ Cancelar</button>
    </form>
  </div>
</div>

<script>
function abrirModalReativacao(funcionario) {
  document.getElementById('loja_original').value = funcionario.loja_id;
  document.getElementById('id').value = funcionario.id;

  document.getElementById('nome').value = funcionario.nome || '';
  document.getElementById('cpf').value = funcionario.cpf || '';
  document.getElementById('codigo').value = funcionario.codigo || '';
  document.getElementById('cc').value = funcionario.cc || '';
  document.getElementById('cargo_id').value = funcionario.cargo_id || '';
  document.getElementById('loja_id').value = funcionario.loja_id || '';
  document.getElementById('contratacao').value = funcionario.contratacao || '';

  document.getElementById('telefone').value = funcionario.telefone || '';
  document.getElementById('email').value = funcionario.email || '';
  document.getElementById('endereco').value = funcionario.endereco || '';
  document.getElementById('aniversario').value = funcionario.nascimento || '';

  document.getElementById('modalReativacao').style.display = 'block';
}

function fecharModal() {
  document.getElementById('modalReativacao').style.display = 'none';
}
</script>

</body>
</html>
