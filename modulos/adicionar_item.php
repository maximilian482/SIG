<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Adicionar Item ao Inventário</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php
require_once '../dados/conexao.php';

$ID_GESTOR = 22;

// Carregar lojas
$lojas = [];
$resLojas = $conn->query("SELECT id, nome, codigo_loja FROM lojas ORDER BY nome");
while ($row = $resLojas->fetch_assoc()) {
  $lojas[$row['id']] = [
    'nome' => $row['nome'],
    'codigo_loja' => $row['codigo_loja']
  ];
}

// Carregar funcionários ativos
$funcionarios = [];
$resFunc = $conn->query("SELECT id, nome FROM funcionarios WHERE desligamento IS NULL ORDER BY nome");
while ($row = $resFunc->fetch_assoc()) {
  $funcionarios[$row['id']] = $row['nome'];
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
  'Telefone IP' => 550
];

// Inserção no banco
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tipo           = $_POST['tipo'] ?? '';
  $descricao      = $_POST['descricao'] ?? '';
  $fabricante     = $_POST['fabricante'] ?? '';
  $setor          = $_POST['setor'] ?? '';
  $valor          = floatval($_POST['valor'] ?? 0);
  $loja_id        = intval($_POST['loja_id'] ?? 0);
  $responsavel_id = intval($_POST['responsavel_id'] ?? $ID_GESTOR);
  if ($responsavel_id <= 0) $responsavel_id = $ID_GESTOR;

  // Gerar patrimônio com trava segura
  $codigoPatrimonio = '';
  if ($loja_id && isset($lojas[$loja_id])) {
    $codigoLoja = $lojas[$loja_id]['codigo_loja'];

    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT ultimo_numero FROM controle_sequencial WHERE loja_id = ? FOR UPDATE");
    $stmt->bind_param("i", $loja_id);
    $stmt->execute();
    $stmt->bind_result($ultimo);
    if ($stmt->fetch()) {
      $novo = $ultimo + 1;
      $stmt->close();

      $stmt = $conn->prepare("UPDATE controle_sequencial SET ultimo_numero = ? WHERE loja_id = ?");
      $stmt->bind_param("ii", $novo, $loja_id);
      $stmt->execute();
    } else {
      $stmt->close();
      $novo = 1;
      $stmt = $conn->prepare("INSERT INTO controle_sequencial (loja_id, ultimo_numero) VALUES (?, ?)");
      $stmt->bind_param("ii", $loja_id, $novo);
      $stmt->execute();
    }

    $conn->commit();

    $codigoPatrimonio = $codigoLoja . str_pad($novo, 3, '0', STR_PAD_LEFT);
  }

  // Verificar duplicidade
  $stmt = $conn->prepare("SELECT id FROM inventario WHERE controle = ?");
  $stmt->bind_param("s", $codigoPatrimonio);
  $stmt->execute();
  $stmt->store_result();
  if ($stmt->num_rows > 0) {
    echo "<p style='color:red;'>❌ Código de patrimônio já existe!</p>";
  } else {
    $stmt->close();
    $stmt = $conn->prepare("
      INSERT INTO inventario (
        loja_id, controle, tipo, descricao, fabricante, setor,
        valor, baixa, motivo_baixa, data_baixa, data_registro, responsavel_id
      ) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL, NULL, NOW(), ?)
    ");
    $stmt->bind_param("isssssdi", $loja_id, $codigoPatrimonio, $tipo, $descricao, $fabricante, $setor, $valor, $responsavel_id);

    if ($stmt->execute()) {
      echo "<p><strong>✅ Item adicionado com sucesso! Patrimônio: $codigoPatrimonio</strong></p>";
    } else {
      echo "<p style='color:red;'>❌ Erro ao salvar: " . $stmt->error . "</p>";
    }
  }
}
?>

<h2>➕ Adicionar novo item ao inventário</h2>

<form method="POST">
  <label>Loja:</label>
  <select name="loja_id" required>
    <option value="">— Selecione —</option>
    <?php foreach ($lojas as $id => $loja): ?>
      <option value="<?= $id ?>"><?= htmlspecialchars($loja['nome']) ?></option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Patrimônio:</label>
    <input type="text" name="controle" value="Será gerado ao salvar" readonly style="background:#f0f0f0; font-weight:bold; color:#555;"><br><br>


  <label>Tipo:</label>
  <select name="tipo" id="tipo" onchange="atualizarValor()">
    <?php foreach (array_keys($valoresPadrao) as $tipo): ?>
      <option value="<?= $tipo ?>"><?= $tipo ?></option>
    <?php endforeach; ?>
    <option value="Outro">Outro</option>
  </select><br><br>

  <label>Descrição:</label><input type="text" name="descricao"><br><br>
  <label>Fabricante:</label><input type="text" name="fabricante"><br><br>

  <label>Setor:</label>
  <select name="setor">
    <?php foreach (['Caixa','Balcão','Depósito','Gerência','Externo','Escritório','Perfumaria'] as $setor): ?>
      <option value="<?= $setor ?>"><?= $setor ?></option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Responsável:</label>
  <select name="responsavel_id">
    <option value="<?= $ID_GESTOR ?>" selected>Gestor</option>
    <?php foreach ($funcionarios as $id => $nome): ?>
      <option value="<?= $id ?>"><?= htmlspecialchars($nome) ?></option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Valor (R$):</label>
  <input type="number" step="0.01" name="valor" id="valor"><br><br>

  <button type="submit">Salvar</button>
</form>

<br>
<a class="btn" href="inventario.php">🔙 Voltar ao inventário</a>

<script>
function atualizarValor() {
  const tipo = document.getElementById('tipo').value;
  const valores = <?= json_encode($valoresPadrao) ?>;
  const campoValor = document.getElementById('valor');
  campoValor.value = valores[tipo] || '';
}
</script>

</body>
</html>
