<?php
require_once '../dados/conexao.php';

$lojaSelecionada = $_GET['loja'] ?? '';
$tipoSelecionado = $_GET['tipo'] ?? '';

// Carregar lojas
$lojas = [];
$resLojas = $conn->query("SELECT id, nome FROM lojas ORDER BY nome");
while ($row = $resLojas->fetch_assoc()) {
  $lojas[$row['id']] = $row['nome'];
}

// Carregar tipos disponíveis
$tiposDisponiveis = [];
$resTipos = $conn->query("SELECT DISTINCT tipo FROM inventario WHERE baixa IS NOT NULL ORDER BY tipo");
while ($row = $resTipos->fetch_assoc()) {
  $tiposDisponiveis[] = $row['tipo'];
}

// Consulta principal
$sql = "
  SELECT i.id, i.controle, i.tipo, i.descricao, i.fabricante, i.setor, i.valor,
         i.motivo_baixa, i.data_baixa,
         l.nome AS nome_loja,
         COALESCE(f.nome, 'Gestor') AS responsavel
  FROM inventario i
  JOIN lojas l ON i.loja_id = l.id
  LEFT JOIN funcionarios f ON i.responsavel_id = f.id
  WHERE i.baixa IS NOT NULL
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

$sql .= " ORDER BY i.tipo, i.descricao";

$stmt = $conn->prepare($sql);
if ($params) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$listaInativos = [];
$valorTotal = 0.0;
while ($row = $result->fetch_assoc()) {
  $listaInativos[] = $row;
  $valorTotal += floatval($row['valor'] ?? 0);
}
$quantidadeTotal = count($listaInativos);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Itens Inativos</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<h2>🗂️ Itens com baixa registrada</h2>

<div style="background:#f0f0f0; padding:10px; margin-bottom:20px; border-radius:5px;">
  <?php
    if ($lojaSelecionada !== '') {
      echo "<p>📍 Loja selecionada: <strong>" . htmlspecialchars($lojas[$lojaSelecionada] ?? $lojaSelecionada) . "</strong></p>";
    }
    if ($tipoSelecionado !== '') {
      echo "<p>🔧 Tipo selecionado: <strong>" . htmlspecialchars($tipoSelecionado) . "</strong></p>";
    }
    echo "<p>🔢 Quantidade de itens inativos: <strong>$quantidadeTotal</strong></p>";
    echo "<p>💰 Valor total dos itens: <strong>R$ " . number_format($valorTotal, 2, ',', '.') . "</strong></p>";
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
    <?php foreach ($tiposDisponiveis as $tipo): ?>
      <option value="<?= htmlspecialchars($tipo) ?>" <?= $tipo === $tipoSelecionado ? 'selected' : '' ?>><?= htmlspecialchars($tipo) ?></option>
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
    <th>Motivo da baixa</th>
    <th>Data da baixa</th>
    <th>Ações</th>
  </tr>

  <?php foreach ($listaInativos as $item): ?>
    <tr>
      <td><?= htmlspecialchars($item['controle']) ?></td>
      <td><?= htmlspecialchars($item['tipo']) ?></td>
      <td><?= htmlspecialchars($item['descricao']) ?></td>
      <td><?= htmlspecialchars($item['fabricante']) ?></td>
      <td><?= htmlspecialchars($item['setor']) ?></td>
      <td><?= htmlspecialchars($item['responsavel']) ?></td>
      <td>R$ <?= number_format($item['valor'], 2, ',', '.') ?></td>
      <td><?= htmlspecialchars($item['nome_loja']) ?></td>
      <td><?= htmlspecialchars($item['motivo_baixa'] ?? '—') ?></td>
      <td><?= htmlspecialchars($item['data_baixa'] ?? '—') ?></td>
      <td>
        <form method="POST" action="reativar_item.php" style="display:inline;">
          <input type="hidden" name="id" value="<?= $item['id'] ?>">
          <button type="submit">♻️</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<br>
<a class="btn" href="inventario.php">🔙 Voltar ao inventário</a>

</body>
</html>
