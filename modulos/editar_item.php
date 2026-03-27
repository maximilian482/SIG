<?php
require_once '../dados/conexao.php';
$conn = conectar();

$id = intval($_GET['id'] ?? 0);

require_once __DIR__ . '/../config/bootstrap.php';

include ROOT_PATH . '/includes/funcoes.php';
include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/menu.php'; 
include ROOT_PATH . '/perfil/menu_perfil.php';

// Buscar item no banco
$stmt = $conn->prepare("
  SELECT 
    i.id,
    i.loja_id,
    i.controle,
    i.nome,
    i.descricao,
    i.setor,
    i.valor,
    i.responsavel_id,   -- ESTE É O CAMPO QUE PRECISAMOS
    l.nome AS nome_loja,
    f.nome AS responsavel_nome,
    c.nome AS categoria_nome,
    c.sigla AS categoria_sigla
  FROM inventario i
  JOIN lojas l ON i.loja_id = l.id
  JOIN inventario_categorias c ON c.id = i.categoria_id
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
$responsaveis = [];

// Sempre incluir o Gestor manualmente
$responsaveis[22] = 'Gestor';

// Agora carregar os demais funcionários
$resFunc = $conn->query("SELECT id, nome FROM funcionarios WHERE desligamento IS NULL ORDER BY nome");
while ($row = $resFunc->fetch_assoc()) {
  $responsaveis[$row['id']] = $row['nome'];
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
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Editar Item</title>
  <link rel="stylesheet" href="../css/inventario.css">

  <style>
    @media (max-width: 600px) {
      form input, form select {
        width: 100% !important;
      }
      .inline { display: block !important; margin-bottom: 10px; }
    }
  </style>
</head>
<body>

<h2>✏️ Editar item do inventário</h2>

<form method="POST" action="salvar_edicao_item.php">
  <input type="hidden" name="id" value="<?= $item['id'] ?>">

  <label>Loja:</label>
  <select name="loja_id" required>
    <?php foreach ($lojas as $idLoja => $nomeLoja): ?>
      <option value="<?= $idLoja ?>" <?= $idLoja == $item['loja_id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($nomeLoja) ?>
      </option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Patrimônio:</label>
  <input type="text" disabled value="<?= htmlspecialchars($item['controle']) ?>" readonly><br><br>

  <label>Nome do item:</label>
  <div style="display:flex; gap:10px; align-items:center;">
    <select name="nome" id="nome" onchange="atualizarValor()" style="flex:1;">
      <?php foreach (array_keys($valoresPadrao) as $n): ?>
        <option value="<?= htmlspecialchars($n) ?>" <?= $item['nome'] === $n ? 'selected' : '' ?>>
          <?= htmlspecialchars($n) ?>
        </option>
      <?php endforeach; ?>
      <option value="Outro" <?= $item['nome'] === 'Outro' ? 'selected' : '' ?>>Outro</option>
    </select>

    <button type="button" onclick="abrirModalNovoItem()" 
            style="padding:8px 12px; background:#006437; color:white; border:none; border-radius:6px; font-size:18px;">
      +
    </button>
  </div>
  <br>

  <label>Descrição:</label>
  <input type="text" name="descricao" value="<?= htmlspecialchars($item['descricao']) ?>"><br><br>

  <label>Setor:</label>
  <div style="display:flex; gap:10px; align-items:center;">
    <select name="setor" id="setor" style="flex:1;">
      <?php foreach (['Caixa','Balcão','Depósito','Gerência','Externo','Escritório','Perfumaria'] as $setor): ?>
        <option value="<?= $setor ?>" <?= $item['setor'] === $setor ? 'selected' : '' ?>><?= $setor ?></option>
      <?php endforeach; ?>
      <option value="Outro" <?= $item['setor'] === 'Outro' ? 'selected' : '' ?>>Outro</option>
    </select>

    <button type="button" onclick="abrirModalNovoSetor()" 
            style="padding:8px 12px; background:#006437; color:white; border:none; border-radius:6px; font-size:18px;">
      +
    </button>
  </div>
  <br>

  <label>Responsável:</label>
    <select name="responsavel_id">
      <?php foreach ($responsaveis as $idResp => $nomeResp): ?>
        <option value="<?= $idResp ?>" <?= ($item['responsavel_id'] == $idResp ? 'selected' : '') ?>>
          <?= htmlspecialchars($nomeResp) ?>
        </option>
      <?php endforeach; ?>
    </select><br><br>

  <label>Valor (R$):</label>
  <input type="number" step="0.01" name="valor" id="valor" value="<?= htmlspecialchars($item['valor']) ?>"><br><br>

  <button type="submit">Salvar alterações</button>
</form>

<br>
<a class="btn" href="inventario.php">🔙 Voltar ao inventário</a>

<!-- Modal Novo Item -->
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

<!-- Modal Novo Setor -->
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
function atualizarValor() {
  const nome = document.getElementById('nome').value;
  const valores = <?= json_encode($valoresPadrao) ?>;
  const campoValor = document.getElementById('valor');
  campoValor.value = valores[nome] || campoValor.value;
}

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

  const select = document.getElementById('nome');
  const option = document.createElement("option");
  option.value = nome;
  option.textContent = nome;
  select.appendChild(option);
  select.value = nome;

  if (valor) {
    document.getElementById('valor').value = valor;
  }

  fecharModalNovoItem();
}

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

  const select = document.getElementById('setor');
  const option = document.createElement("option");
  option.value = nome;
  option.textContent = nome;
  select.appendChild(option);
  select.value = nome;

  fecharModalNovoSetor();
}
</script>

</body>
</html>
