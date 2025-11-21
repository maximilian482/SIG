<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Inventário por Loja</title>
  <link rel="stylesheet" href="../css/st.css">
</head>
<body>

<?php
require_once '../includes/funcoes.php';
$conn = conectar();

// ID fixo do funcionário fictício "Gestor"
$ID_GESTOR = 1;

// Inativar item (via POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
  $id = intval($_POST['id']);
  $motivo = trim($_POST['motivo'] ?? '—');

  $stmt = $conn->prepare("UPDATE inventario SET ativo = 0, motivo_baixa = ? WHERE id = ?");
  $stmt->bind_param("si", $motivo, $id);
  $stmt->execute();
}

// Filtros
$lojaSelecionada = $_GET['loja'] ?? '';
$tipoSelecionado = $_GET['tipo'] ?? '';
$responsavelSelecionado = $_GET['responsavel'] ?? '';

// Carregar lojas
$lojas = [];
$resLojas = $conn->query("SELECT id, nome FROM lojas ORDER BY nome");
while ($row = $resLojas->fetch_assoc()) {
  $lojas[$row['id']] = $row['nome'];
}

$listaFiltrada = [];
$valorTotal = 0;
$quantidadeTotal = 0;

// Consulta principal com filtros
$sql = "
  SELECT i.id, i.controle, i.tipo, i.descricao, i.fabricante, i.setor, i.valor,
         l.nome AS nome_loja,
         f.nome AS responsavel
  FROM inventario i
  JOIN lojas l ON i.loja_id = l.id
  JOIN funcionarios f ON i.responsavel_id = f.id
  WHERE i.baixa IS NULL
";

$params = [];
$types = '';

if ($lojaSelecionada !== '') {
  $sql .= " AND i.loja_id = ?";
  $params[] = $lojaSelecionada;
  $types .= 'i';
}
if ($tipoSelecionado !== '') {
  $sql .= " AND i.tipo = ?";
  $params[] = $tipoSelecionado;
  $types .= 's';
}
if ($responsavelSelecionado !== '') {
  $sql .= " AND f.nome = ?";
  $params[] = $responsavelSelecionado;
  $types .= 's';
}

$sql .= " ORDER BY i.tipo, i.descricao";

$stmt = $conn->prepare($sql);
if ($params) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

while ($item = $result->fetch_assoc()) {
  $listaFiltrada[] = $item;
  $valorTotal += $item['valor'] ?? 0;
  $quantidadeTotal++;
}

// Carregar responsáveis únicos
$responsaveisDisponiveis = [];
$resResp = $conn->query("
  SELECT DISTINCT f.nome AS responsavel
  FROM inventario i
  JOIN funcionarios f ON i.responsavel_id = f.id
  WHERE i.baixa IS NULL
  ORDER BY f.nome
");

while ($row = $resResp->fetch_assoc()) {
  $responsavel = $row['responsavel'];
  if (!in_array($responsavel, $responsaveisDisponiveis)) {
    $responsaveisDisponiveis[] = $responsavel;
  }
}
?>

<h2>📦 Inventário Ativo</h2>
<div style="background:#f0f0f0; padding:10px; margin-bottom:20px; border-radius:5px;">
  <?php
    if ($responsavelSelecionado) echo "<p>🙋 Responsável <strong>$responsavelSelecionado</strong></p>";
    if ($lojaSelecionada) echo "<p>📍 Loja <strong>" . ($lojas[$lojaSelecionada] ?? $lojaSelecionada) . "</strong></p>";
    if ($tipoSelecionado) echo "<p>🔧 Tipo <strong>$tipoSelecionado</strong></p>";
    echo "<p>🔢 Quantidade de itens: <strong>$quantidadeTotal</strong></p>";
    echo "<p>💰 Valor total: <strong>R$ " . number_format($valorTotal, 2, ',', '.') . "</strong></p>";
  ?>
</div>

<form method="GET" style="margin-bottom: 20px;">
  <label>Loja:</label>
  <select name="loja" onchange="this.form.submit()">
    <option value="">— Todas —</option>
    <?php foreach ($lojas as $id => $nome): ?>
      <option value="<?= $id ?>" <?= $id == $lojaSelecionada ? 'selected' : '' ?>><?= htmlspecialchars($nome) ?></option>
    <?php endforeach; ?>
  </select>

  <label style="margin-left:20px;">Tipo:</label>
  <select name="tipo" onchange="this.form.submit()">
    <option value="">— Todos —</option>
    <?php
    $resTipos = $conn->query("SELECT DISTINCT tipo FROM inventario WHERE baixa IS NULL AND tipo IS NOT NULL ORDER BY tipo");
    while ($row = $resTipos->fetch_assoc()):
      $tipo = $row['tipo'];
    ?>
      <option value="<?= $tipo ?>" <?= $tipo === $tipoSelecionado ? 'selected' : '' ?>><?= $tipo ?></option>
    <?php endwhile; ?>
  </select>

  <label style="margin-left:20px;">Responsável:</label>
  <select name="responsavel" onchange="this.form.submit()">
    <option value="">— Todos —</option>
    <?php foreach ($responsaveisDisponiveis as $nome): ?>
      <option value="<?= $nome ?>" <?= $nome === $responsavelSelecionado ? 'selected' : '' ?>><?= $nome ?></option>
    <?php endforeach; ?>
  </select>
</form>

<table>
  <tr>
    <th>Patrimônio</th>
    <th>Tipo</th>
    <th>Descrição</th>
    <th>Fabricante</th>
    <th>Setor</th>
    <th>Responsável</th>
    <th>Valor</th>
    <th>Loja</th>
    <th>Ações</th>
  </tr>

  <?php foreach ($listaFiltrada as $item): ?>
    <tr>
      <td><?= htmlspecialchars($item['controle'] ?? '—') ?></td>
      <td><?= htmlspecialchars($item['tipo'] ?? '—') ?></td>
      <td><?= htmlspecialchars($item['descricao'] ?? '—') ?></td>
      <td><?= htmlspecialchars($item['fabricante'] ?? '—') ?></td>
      <td><?= htmlspecialchars($item['setor'] ?? '—') ?></td>
      <td><?= htmlspecialchars($item['responsavel']) ?></td>
      <td>R$ <?= number_format($item['valor'] ?? 0, 2, ',', '.') ?></td>
      <td><?= htmlspecialchars($item['nome_loja'] ?? '—') ?></td>
      <td>
        <a href="editar_item.php?id=<?= $item['id'] ?>">✏️</a> |
        <button onclick="abrirModal(<?= $item['id'] ?>, '<?= htmlspecialchars($item['tipo']) ?>')">🗑️</button>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<br>
<a class="btn" href="../index.php" style="margin-top:20px;">🏠</a>
<a class="btn" href="adicionar_item.php">➕</a>
<a class="btn" href="itens_inativos.php" style="margin-left:10px;">🗂️ Inativos</a>

<!-- Modal para baixa do produto -->
<div id="modalInativar" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
  <div style="background:#fff; margin:10% auto; padding:20px; width:400px; border-radius:8px;">
    <h3>🗑️ Confirmar baixa de item</h3>
    <p id="modalTexto"></p>
    <form method="POST" action="inativar_item.php">
      <input type="hidden" name="id" id="modalId">
      <label>Motivo da baixa:</label><br>
      <textarea name="motivo_baixa" rows="3" required></textarea><br><br>
      <button type="submit">Confirmar baixa</button>
      <button type="button" onclick="fecharModal()">Cancelar</button>
    </form>
  </div>
</div>

<script>
function abrirModal(id, tipo) {
  document.getElementById('modalId').value = id;
  document.getElementById('modalTexto').innerHTML = `Você tem certeza que deseja dar baixa no item <strong>${tipo}</strong>?`;
  document.getElementById('modalInativar').style.display = 'block';
}
function fecharModal() {
  document.getElementById('modalInativar').style.display = 'none';
}
</script>


</body>
</html>
