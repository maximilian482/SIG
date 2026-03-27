<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Adicionar Item ao Inventário</title>
  <link rel="stylesheet" href="../css/inventarioform.css">
</head>
<body>
  

<?php
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';

include ROOT_PATH . '/includes/funcoes.php';
include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/menu.php'; 
include ROOT_PATH . '/perfil/menu_perfil.php';

$ID_GESTOR = 22;

/* ============================
   CARREGAR LOJAS
============================ */
$lojas = [];
$resLojas = $conn->query("SELECT id, nome FROM lojas ORDER BY nome");
while ($row = $resLojas->fetch_assoc()) {
  $lojas[$row['id']] = $row['nome'];
}

/* ============================
   CARREGAR FUNCIONÁRIOS
============================ */
$funcionarios = [];
$resFunc = $conn->query("SELECT id, nome FROM funcionarios WHERE desligamento IS NULL ORDER BY nome");
while ($row = $resFunc->fetch_assoc()) {
  $funcionarios[$row['id']] = $row['nome'];
}

/* ============================
   CARREGAR CATEGORIAS
============================ */
$categorias = [];
$resCat = $conn->query("SELECT id, nome, sigla FROM inventario_categorias ORDER BY nome");
while ($row = $resCat->fetch_assoc()) {
  $categorias[$row['id']] = [
    'nome' => $row['nome'],
    'sigla' => $row['sigla']
  ];
}

/* ============================
   VALORES PADRÃO
============================ */
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

/* ============================
   PROCESSAR FORMULÁRIO
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $categoria_id   = intval($_POST['categoria_id'] ?? 0);
  $nome           = $_POST['nome'] ?? '';
  $descricao      = $_POST['descricao'] ?? '';
  $setor          = $_POST['setor'] ?? '';
  $valor          = floatval($_POST['valor'] ?? 0);
  $loja_id        = intval($_POST['loja_id'] ?? 0);
  $responsavel_id = intval($_POST['responsavel_id'] ?? $ID_GESTOR);
  if ($responsavel_id <= 0) $responsavel_id = $ID_GESTOR;

  /* ============================
   GERAR NÚMERO SEQUENCIAL GLOBAL
============================ */
$sql = "SELECT MAX(numero_sequencial) AS ultimo FROM inventario";
$res = $conn->query($sql);
$row = $res->fetch_assoc();
$novoNumero = intval($row['ultimo']) + 1;

/* ============================
   FORMATAR NÚMERO (001, 002, 010, 123...)
============================ */
$numeroFormatado = str_pad($novoNumero, 3, '0', STR_PAD_LEFT);

/* ============================
   BUSCAR SIGLA DA CATEGORIA
============================ */
$sigla = $categorias[$categoria_id]['sigla'] ?? 'SEM';

/* ============================
   PATRIMÔNIO VISUAL FINAL
   Ex.: TEC001
============================ */
$codigoPatrimonioVisual = $sigla . $numeroFormatado;


  /* ============================
     SALVAR NO BANCO
  ============================ */
  $stmt = $conn->prepare("
    INSERT INTO inventario 
    (numero_sequencial, controle, categoria_id, nome, descricao, setor, valor, loja_id, responsavel_id, data_registro)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
  ");

  $stmt->bind_param(
    "isisssdii",

    $novoNumero,
    $codigoPatrimonioVisual,
    $categoria_id,
    $nome,
    $descricao,
    $setor,
    $valor,
    $loja_id,
    $responsavel_id
  );

  if ($stmt->execute()) {
    echo "<p><strong>✅ Item adicionado com sucesso! Patrimônio: $codigoPatrimonioVisual</strong></p>";
  } else {
    echo "<p style='color:red;'>❌ Erro ao salvar: " . $stmt->error . "</p>";
  }
}
?>

<h2>➕ Adicionar novo item ao inventário</h2>

<form method="POST">

  <label>Loja:</label>
  <select name="loja_id" required>
    <option value="">— Selecione —</option>
    <?php foreach ($lojas as $id => $nome): ?>
      <option value="<?= $id ?>"><?= htmlspecialchars($nome) ?></option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Categoria:</label>
  <select name="categoria_id" required>
    <option value="">— Selecione —</option>
    <?php foreach ($categorias as $id => $cat): ?>
      <option value="<?= $id ?>"><?= htmlspecialchars($cat['nome']) ?></option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Patrimônio:</label>
  <input type="text" value="Será gerado automaticamente" readonly
         style="background:#f0f0f0; font-weight:bold; color:#555;"><br><br>

  <label>Nome do item:</label>
    <div style="display:flex; gap:10px; align-items:center;">
  
  <select name="nome" id="nome" onchange="atualizarValor()" style="flex:1;">
    <?php foreach (array_keys($valoresPadrao) as $n): ?>
      <option value="<?= htmlspecialchars($n) ?>"><?= htmlspecialchars($n) ?></option>
    <?php endforeach; ?>
    <option value="Outro">Outro</option>
  </select>

  <button type="button" onclick="abrirModalNovoItem()" 
          style="padding:8px 12px; background:#006437; color:white; border:none; border-radius:6px; font-size:18px;">
    +
  </button>

</div>
<br>


  <label>Descrição:</label>
  <input type="text" name="descricao"><br><br>

  <label>Setor:</label>
<div style="display:flex; gap:10px; align-items:center;">

  <select name="setor" id="setor" style="flex:1;">
    <?php foreach (['Caixa','Balcão','Depósito','Gerência','Externo','Escritório','Perfumaria'] as $setor): ?>
      <option value="<?= htmlspecialchars($setor) ?>"><?= htmlspecialchars($setor) ?></option>
    <?php endforeach; ?>
    <option value="Outro">Outro</option>
  </select>

  <button type="button" onclick="abrirModalNovoSetor()" 
          style="padding:8px 12px; background:#006437; color:white; border:none; border-radius:6px; font-size:18px;">
    +
  </button>

</div>
<br>


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

<!-- Moda para cadastrar novo ítem -->
 <div id="modalNovoItem" class="modal">
  <div class="modal-content" style="max-width:400px;">
    <h3>➕ Novo Item</h3>

    <label>Nome do item:</label>
    <input type="text" id="novoItemNome" style="width:100%; padding:8px; margin-bottom:10px;">

    <label>Valor sugerido (R$):</label>
    <input type="number" id="novoItemValor" step="0.01" style="width:100%; padding:8px; margin-bottom:10px;">

    <div class="modal-acoes">
      <button class="btn confirmar" onclick="salvarNovoItem()">Salvar</button>
      <button class="btn cancelar" onclick="fecharModalNovoItem()">Cancelar</button>
    </div>
  </div>
</div>

<script>
function abrirModalNovoItem() {
  document.getElementById('modalNovoItem').style.display = 'block';
}

function fecharModalNovoItem() {
  document.getElementById('modalNovoItem').style.display = 'none';
}

function salvarNovoItem() {
  const nome = document.getElementById('novoItemNome').value.trim();
  const valor = document.getElementById('novoItemValor').value.trim();

  if (!nome) {
    alert("Digite o nome do item.");
    return;
  }

  // Adiciona ao select
  const select = document.getElementById('nome');
  const option = document.createElement("option");
  option.value = nome;
  option.textContent = nome;
  select.appendChild(option);
  select.value = nome;

  // Preenche o valor sugerido
  if (valor) {
    document.getElementById('valor').value = valor;
  }

  fecharModalNovoItem();
}
</script>

<!-- Modal para criar novo setor -->
 <div id="modalNovoSetor" class="modal">
  <div class="modal-content" style="max-width:400px;">
    <h3>➕ Novo Setor</h3>

    <label>Nome do setor:</label>
    <input type="text" id="novoSetorNome" style="width:100%; padding:8px; margin-bottom:10px;">

    <div class="modal-acoes">
      <button class="btn confirmar" onclick="salvarNovoSetor()">Salvar</button>
      <button class="btn cancelar" onclick="fecharModalNovoSetor()">Cancelar</button>
    </div>
  </div>
</div>

<script>
function abrirModalNovoSetor() {
  document.getElementById('modalNovoSetor').style.display = 'block';
}

function fecharModalNovoSetor() {
  document.getElementById('modalNovoSetor').style.display = 'none';
}

function salvarNovoSetor() {
  const nome = document.getElementById('novoSetorNome').value.trim();

  if (!nome) {
    alert("Digite o nome do setor.");
    return;
  }

  // Adiciona ao select
  const select = document.getElementById('setor');
  const option = document.createElement("option");
  option.value = nome;
  option.textContent = nome;
  select.appendChild(option);
  select.value = nome;

  fecharModalNovoSetor();
}
</script>

<script>
function atualizarValor() {
  const nome = document.getElementById('nome').value;
  const valores = <?= json_encode($valoresPadrao) ?>;
  const campoValor = document.getElementById('valor');
  campoValor.value = valores[nome] || '';
}
</script>

</body>
</html>
