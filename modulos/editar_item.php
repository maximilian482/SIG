<?php
require_once '../dados/conexao.php';

$id = intval($_GET['id'] ?? 0);

// Buscar item no banco
$stmt = $conn->prepare("
  SELECT i.*, l.nome AS nome_loja, f.nome AS nome_funcionario
  FROM inventario i
  JOIN lojas l ON i.loja_id = l.id
  LEFT JOIN funcionarios f ON i.responsavel_id = f.id
  WHERE i.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

if (!$item) {
  echo "<p><strong>⚠️ Item não encontrado.</strong></p>";
  echo '<a href="inventario.php">🔙 Voltar</a>';
  exit;
}

// Carregar lojas
$lojas = [];
$resLojas = $conn->query("SELECT id, nome FROM lojas ORDER BY nome");
while ($row = $resLojas->fetch_assoc()) {
  $lojas[$row['id']] = $row['nome'];
}

// Carregar funcionários ativos
$responsaveis = ['Gestor'];
$resFunc = $conn->query("SELECT nome FROM funcionarios WHERE desligamento IS NULL ORDER BY nome");
while ($row = $resFunc->fetch_assoc()) {
  $responsaveis[] = $row['nome'];
}

// Valores padrão por tipo
$valoresPadrao = [
  'Monitor' => 300,
  'Teclado' => 30,
  'Mouse' => 20,
  'Leitor' => 105,
  'Fone' => 75,
  'CPU' => 650,
  'Impressora A4' => 1150,
  'Impressora Cupom' => 550,
  'Cabo' => 30,
  'Telefone' => 550
];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Editar Item</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .inline { display: inline-block; margin-right: 10px; }
    .btn-add { cursor: pointer; font-weight: bold; margin-left: 5px; }
  </style>
</head>
<body>

<h2>✏️ Editar item do inventário</h2>

<form method="POST" action="salvar_edicao_item.php">
  <input type="hidden" name="id" value="<?= $item['id'] ?>">

  <label>Loja:</label>
  <select name="nova_loja" required>
    <?php foreach ($lojas as $idLoja => $nomeLoja): ?>
      <option value="<?= $idLoja ?>" <?= $idLoja == $item['loja_id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($nomeLoja) ?>
      </option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Patrimônio:</label>
  <input type="text" disabled value="<?= htmlspecialchars($item['controle']) ?>" readonly><br><br>

  <div class="inline">
    <label>Tipo:</label>
    <select name="tipo" disabled>
      <option value="<?= htmlspecialchars($item['tipo']) ?>"><?= htmlspecialchars($item['tipo']) ?></option>
    </select>
    <input type="hidden" name="tipo" value="<?= htmlspecialchars($item['tipo']) ?>">
  </div><br><br>

  <label>Descrição:</label><input type="text" name="descricao" value="<?= htmlspecialchars($item['descricao']) ?>"><br><br>
  <label>Fabricante:</label><input type="text" name="fabricante" value="<?= htmlspecialchars($item['fabricante']) ?>"><br><br>

  <div class="inline">
    <label>Setor:</label>
    <select name="setor" id="setor">
      <?php foreach (['Caixa','Balcão','Depósito','Gerência','Externo','Escritório','Perfumaria'] as $setor): ?>
        <option value="<?= $setor ?>" <?= $item['setor'] === $setor ? 'selected' : '' ?>><?= $setor ?></option>
      <?php endforeach; ?>
      <option value="Outro" <?= $item['setor'] === 'Outro' ? 'selected' : '' ?>>Outro</option>
    </select>
    <span class="btn-add" onclick="adicionarOpcao('setor')">+</span>
  </div><br><br>

  <label>Responsável:</label>
  <select name="responsavel">
    <?php foreach ($responsaveis as $nome): ?>
      <option value="<?= htmlspecialchars($nome) ?>" <?= $item['nome_funcionario'] === $nome ? 'selected' : '' ?>><?= htmlspecialchars($nome) ?></option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Valor (R$):</label>
  <input type="number" step="0.01" name="valor" id="valor" value="<?= htmlspecialchars($item['valor']) ?>"><br><br>

  <button type="submit">Salvar alterações</button>
</form>

<br>
<a class="btn" href="inventario.php">🔙 Voltar ao inventário</a>

<script>
function adicionarOpcao(campoId) {
  const select = document.getElementById(campoId);
  const novaOpcao = prompt("Digite o novo valor:");
  if (novaOpcao) {
    const option = document.createElement("option");
    option.value = novaOpcao;
    option.text = novaOpcao;
    select.add(option);
    select.value = novaOpcao;
  }
}
</script>

</body>
</html>
