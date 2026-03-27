<?php
require_once '../includes/funcoes.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/menu.php'; 
include ROOT_PATH . '/perfil/menu_perfil.php';

// Inativar item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
  $id = intval($_POST['id']);
  $motivo = trim($_POST['motivo'] ?? '—');

  $stmt = $conn->prepare("UPDATE inventario SET baixa = CURDATE(), motivo_baixa = ? WHERE id = ?");
  $stmt->bind_param("si", $motivo, $id);
  $stmt->execute();
}

// Filtros
$lojaSelecionada = $_GET['loja'] ?? '';
$categoriaSelecionada = $_GET['categoria'] ?? '';
$nomeSelecionado = $_GET['nome'] ?? '';
$responsavelSelecionado = $_GET['responsavel'] ?? '';
$controleSelecionado = $_GET['controle'] ?? '';

// Paginação
$itensPorPagina = intval($_GET['por_pagina'] ?? 10);
if ($itensPorPagina <= 0) $itensPorPagina = 10;

$paginaAtual = intval($_GET['pagina'] ?? 1);
if ($paginaAtual <= 0) $paginaAtual = 1;

$offset = ($paginaAtual - 1) * $itensPorPagina;

// Carregar lojas
$lojas = [];
$resLojas = $conn->query("SELECT id, nome FROM lojas ORDER BY nome");
while ($row = $resLojas->fetch_assoc()) {
  $lojas[$row['id']] = $row['nome'];
}

// Carregar categorias
$categorias = [];
$resCat = $conn->query("SELECT id, nome FROM inventario_categorias ORDER BY nome");
while ($row = $resCat->fetch_assoc()) {
  $categorias[$row['id']] = $row['nome'];
}

// Carregar responsáveis (inclui Gestor manualmente)
$responsaveisDisponiveis = [22 => 'Gestor'];

$resResp = $conn->query("
  SELECT DISTINCT f.id, f.nome
  FROM inventario i
  LEFT JOIN funcionarios f ON i.responsavel_id = f.id
  WHERE i.baixa IS NULL
  ORDER BY f.nome
");

while ($row = $resResp->fetch_assoc()) {
  if ($row['id']) {
    $responsaveisDisponiveis[$row['id']] = $row['nome'];
  }
}

// Consulta principal (com paginação)
$sql = "
  SELECT i.id, i.controle, i.nome, i.descricao, i.setor, i.valor,
         c.nome AS categoria,
         c.sigla AS sigla_categoria,
         l.nome AS nome_loja,
         COALESCE(f.nome, 'Gestor') AS responsavel
  FROM inventario i
  JOIN lojas l ON i.loja_id = l.id
  LEFT JOIN funcionarios f ON i.responsavel_id = f.id
  JOIN inventario_categorias c ON i.categoria_id = c.id
  WHERE i.baixa IS NULL
";

$params = [];
$types = '';

if ($lojaSelecionada !== '') {
  $sql .= " AND i.loja_id = ?";
  $params[] = $lojaSelecionada;
  $types .= 'i';
}
if ($controleSelecionado !== '') {
  $sql .= " AND i.controle LIKE ?";
  $params[] = "%$controleSelecionado%";
  $types .= 's';
}
if ($categoriaSelecionada !== '') {
  $sql .= " AND i.categoria_id = ?";
  $params[] = $categoriaSelecionada;
  $types .= 'i';
}
if ($nomeSelecionado !== '') {
  $sql .= " AND i.nome = ?";
  $params[] = $nomeSelecionado;
  $types .= 's';
}
if ($responsavelSelecionado !== '') {
  $sql .= " AND i.responsavel_id = ?";
  $params[] = $responsavelSelecionado;
  $types .= 'i';
}

$sqlCount = "SELECT COUNT(*) AS total FROM ($sql) AS sub";
$stmtCount = $conn->prepare($sqlCount);
if ($params) $stmtCount->bind_param($types, ...$params);
$stmtCount->execute();
$totalItens = $stmtCount->get_result()->fetch_assoc()['total'];

$totalPaginas = ceil($totalItens / $itensPorPagina);

$sql .= " ORDER BY c.nome, i.nome LIMIT ? OFFSET ?";
$params[] = $itensPorPagina;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$listaFiltrada = [];
$valorTotal = 0;

while ($item = $result->fetch_assoc()) {
  $listaFiltrada[] = $item;
  $valorTotal += $item['valor'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Inventário por Loja</title>
  <link rel="stylesheet" href="../css/inventario.css">
</head>
<body>

<div class="container">
  <h2>📦 Inventário Ativo</h2>

  <div class="resumo">
    <?php
      if ($controleSelecionado) echo "<p>🏷️ Patrimônio <strong>$controleSelecionado</strong></p>";
      if ($responsavelSelecionado) echo "<p>🙋 Responsável <strong>{$responsaveisDisponiveis[$responsavelSelecionado]}</strong></p>";
      if ($lojaSelecionada) echo "<p>📍 Loja <strong>{$lojas[$lojaSelecionada]}</strong></p>";
      if ($categoriaSelecionada) echo "<p>📂 Categoria <strong>{$categorias[$categoriaSelecionada]}</strong></p>";
      if ($nomeSelecionado) echo "<p>🔧 Item <strong>$nomeSelecionado</strong></p>";
      echo "<p>🔢 Quantidade: <strong>$totalItens</strong></p>";
      echo "<p>💰 Valor total: <strong>R$ " . number_format($valorTotal, 2, ',', '.') . "</strong></p>";
    ?>
  </div>

  <form method="GET" class="filtros">

    <label>Loja:</label>
    <select name="loja" onchange="this.form.submit()">
      <option value="">— Todas —</option>
      <?php foreach ($lojas as $id => $nome): ?>
        <option value="<?= $id ?>" <?= $id == $lojaSelecionada ? 'selected' : '' ?>><?= $nome ?></option>
      <?php endforeach; ?>
    </select>

    <label>Categoria:</label>
    <select name="categoria" onchange="this.form.submit()">
      <option value="">— Todas —</option>
      <?php foreach ($categorias as $id => $nome): ?>
        <option value="<?= $id ?>" <?= $id == $categoriaSelecionada ? 'selected' : '' ?>><?= $nome ?></option>
      <?php endforeach; ?>
    </select>

    <label>Item:</label>
    <select name="nome" onchange="this.form.submit()">
      <option value="">— Todos —</option>
      <?php
      $resNomes = $conn->query("SELECT DISTINCT nome FROM inventario WHERE baixa IS NULL ORDER BY nome");
      while ($row = $resNomes->fetch_assoc()):
      ?>
        <option value="<?= $row['nome'] ?>" <?= $row['nome'] === $nomeSelecionado ? 'selected' : '' ?>>
          <?= $row['nome'] ?>
        </option>
      <?php endwhile; ?>
    </select>

    <label>Responsável:</label>
    <select name="responsavel" onchange="this.form.submit()">
      <option value="">— Todos —</option>
      <?php foreach ($responsaveisDisponiveis as $id => $nome): ?>
        <option value="<?= $id ?>" <?= $id == $responsavelSelecionado ? 'selected' : '' ?>><?= $nome ?></option>
      <?php endforeach; ?>
    </select>

    <label>Patrimônio:</label>
    <input type="text" name="controle" value="<?= htmlspecialchars($controleSelecionado) ?>"
           placeholder="Ex: TEC001"
           onchange="this.form.submit()">

   <a href="inventario.php" class="btn limpar-filtros">Limpar filtros</a>

  </form>

  <?php if (empty($listaFiltrada)): ?>
    <p style="color:red; font-weight:bold;">Nenhum item encontrado.</p>
  <?php else: ?>
    <table class="tabela-inventario">
      <thead>
        <tr>
          <th>Patrimônio</th>
          <th>Nome</th>
          <th>Loja</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($listaFiltrada as $item): ?>
          <tr>
            <td><?= htmlspecialchars($item['controle']) ?></td>
            <td><?= htmlspecialchars($item['nome']) ?></td>
            <td><?= htmlspecialchars($item['nome_loja']) ?></td>
            <td class="acoes">
              <button type="button" class="acao detalhes" onclick="abrirDetalhes(<?= $item['id'] ?>)">🔍</button>
              <a href="editar_item.php?id=<?= $item['id'] ?>" class="acao editar">✏️</a>
              <button type="button" class="acao excluir"
                      onclick="abrirModal(<?= $item['id'] ?>, '<?= htmlspecialchars($item['nome']) ?>')">🗑️</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <!-- Paginação -->
  <div class="paginacao-container">

  <!-- Seletor de itens por página -->
  <form method="GET" class="por-pagina-form">
    <?php 
      // preserva todos os filtros
      foreach ($_GET as $k => $v) {
        if ($k !== 'por_pagina' && $k !== 'pagina') {
          echo "<input type='hidden' name='$k' value='$v'>";
        }
      }
    ?>
  </form>

  <!-- Paginação inteligente -->
  <div class="paginacao-wrapper">

  <div class="paginacao">
    <?php if ($paginaAtual > 1): ?>
      <a class="page-btn" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $paginaAtual - 1])) ?>">⟵ Anterior</a>
    <?php endif; ?>

    <div class="page-numbers">

      <!-- Página 1 -->
      <a class="page-number <?= $paginaAtual == 1 ? 'ativo' : '' ?>"
         href="?<?= http_build_query(array_merge($_GET, ['pagina' => 1])) ?>">
        1
      </a>

      <!-- Reticências antes -->
      <?php if ($paginaAtual > 4): ?>
        <span class="ellipsis">…</span>
      <?php endif; ?>

      <!-- Bloco central -->
      <?php
        $inicio = max(2, $paginaAtual - 3);
        $fim = min($totalPaginas - 1, $paginaAtual + 3);

        for ($i = $inicio; $i <= $fim; $i++):
      ?>
        <a class="page-number <?= $paginaAtual == $i ? 'ativo' : '' ?>"
           href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>

      <!-- Reticências depois -->
      <?php if ($paginaAtual < $totalPaginas - 3): ?>
        <span class="ellipsis">…</span>
      <?php endif; ?>

      <!-- Última página -->
      <?php if ($totalPaginas > 1): ?>
        <a class="page-number <?= $paginaAtual == $totalPaginas ? 'ativo' : '' ?>"
           href="?<?= http_build_query(array_merge($_GET, ['pagina' => $totalPaginas])) ?>">
          <?= $totalPaginas ?>
        </a>
      <?php endif; ?>

    </div>

    <?php if ($paginaAtual < $totalPaginas): ?>
      <a class="page-btn" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $paginaAtual + 1])) ?>">Próxima ⟶</a>
    <?php endif; ?>

    <!-- Seletor ao lado da paginação -->
  <form method="GET" class="por-pagina">
    <?php 
      foreach ($_GET as $k => $v) {
        if ($k !== 'por_pagina' && $k !== 'pagina') {
          echo "<input type='hidden' name='$k' value='$v'>";
        }
      }
    ?>
    
    <select name="por_pagina" onchange="this.form.submit()">
      <?php foreach ([10, 20, 50, 100] as $qtd): ?>
        <option value="<?= $qtd ?>" <?= $qtd == $itensPorPagina ? 'selected' : '' ?>>
          <?= $qtd ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>
  </div>

  

</div>





  <div class="acoes-gerais">
    <a class="btn" href="../modulos/gestao.php">🏠 Voltar</a>
    <a class="btn" href="adicionar_item.php">➕ Adicionar</a>
    <a class="btn" href="itens_inativos.php">🗂️ Inativos</a>
  </div>
</div>

<!-- Modal de detalhes -->
<div id="modalDetalhes" class="modal">
  <div class="modal-content" style="max-width:500px;">
    <h3>📄 Detalhes do Item</h3>
    <div id="conteudoDetalhes">Carregando...</div>
    <div class="modal-acoes">
      <button class="btn cancelar" onclick="fecharModalDetalhes()">Fechar</button>
    </div>
  </div>
</div>

<!-- Modal de baixa -->
<div id="modalInativar" class="modal">
  <div class="modal-content">
    <h3>🗑️ Confirmar baixa</h3>
    <p id="modalTexto"></p>
    <form method="POST" action="inativar_item.php">
      <input type="hidden" name="id" id="modalId">
      <label>Motivo:</label>
      <textarea name="motivo" rows="3" required></textarea>
      <div class="modal-acoes">
        <button type="submit" class="btn confirmar">Confirmar</button>
        <button type="button" class="btn cancelar" onclick="fecharModal()">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirDetalhes(id) {
  document.getElementById('modalDetalhes').style.display = 'block';
  document.getElementById('conteudoDetalhes').innerHTML = "Carregando...";

  fetch("detalhes_item_ajax.php?id=" + id)
    .then(res => res.json())
    .then(data => {
      let html = `
        <p><strong>Patrimônio:</strong> ${data.controle}</p>
        <p><strong>Categoria:</strong> ${data.categoria_nome}</p>
        <p><strong>Nome:</strong> ${data.nome}</p>
        <p><strong>Descrição:</strong> ${data.descricao || '—'}</p>
        <p><strong>Setor:</strong> ${data.setor}</p>
        <p><strong>Valor:</strong> R$ ${parseFloat(data.valor).toFixed(2)}</p>
        <p><strong>Loja:</strong> ${data.loja_nome}</p>
        <p><strong>Responsável:</strong> ${data.responsavel_nome}</p>
        <p><strong>Data Registro:</strong> ${
          data.data_registro 
            ? new Date(data.data_registro).toLocaleDateString('pt-BR')
            : '—'
        }</p>

      `;
      document.getElementById('conteudoDetalhes').innerHTML = html;
    });
}

function fecharModalDetalhes() {
  document.getElementById('modalDetalhes').style.display = 'none';
}

function abrirModal(id, nome) {
  document.getElementById('modalId').value = id;
  document.getElementById('modalTexto').innerHTML =
    `Deseja dar baixa no item <strong>${nome}</strong>?`;
  document.getElementById('modalInativar').style.display = 'block';
}

function fecharModal() {
  document.getElementById('modalInativar').style.display = 'none';
}
</script>

</body>
</html>
