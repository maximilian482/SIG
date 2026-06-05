<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

$lojaSelecionada = $_GET['loja'] ?? '';
$nomeSelecionado = $_GET['nome'] ?? '';

/* ============================
   CARREGAR LOJAS
============================ */
$lojas = [];
$resLojas = $conn->query("SELECT id, nome FROM lojas ORDER BY nome");
while ($row = $resLojas->fetch_assoc()) {
    $lojas[$row['id']] = $row['nome'];
}

/* ============================
   CARREGAR NOMES DE ITENS INATIVOS
============================ */
$nomesDisponiveis = [];
$resNomes = $conn->query("SELECT DISTINCT nome FROM inventario WHERE baixa IS NOT NULL ORDER BY nome");
while ($row = $resNomes->fetch_assoc()) {
    $nomesDisponiveis[] = $row['nome'];
}

/* ============================
   CONSULTA PRINCIPAL
============================ */
$sql = "
    SELECT 
        i.id,
        i.controle,
        i.nome,
        i.descricao,
        i.setor,
        i.valor,
        i.motivo_baixa,
        i.data_baixa,
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

if ($nomeSelecionado !== '') {
    $sql .= " AND i.nome = ?";
    $params[] = $nomeSelecionado;
    $types .= 's';
}

$sql .= " ORDER BY i.nome, i.descricao";

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

/* ============================
   INÍCIO DO HTML (layout)
============================ */
ob_start();
?>

<link rel="stylesheet" href="../css/inventario.css">

<div class="container">
    <h2>🗂️ Itens Inativos</h2>

    <div class="resumo">
        <?php
            if ($lojaSelecionada) echo "<p>📍 Loja <strong>{$lojas[$lojaSelecionada]}</strong></p>";
            if ($nomeSelecionado) echo "<p>🔧 Item <strong>$nomeSelecionado</strong></p>";
            echo "<p>🔢 Quantidade: <strong>$quantidadeTotal</strong></p>";
            echo "<p>💰 Valor total: <strong>R$ " . number_format($valorTotal, 2, ',', '.') . "</strong></p>";
        ?>
    </div>

    <form method="GET" class="filtros">

        <label>Loja:</label>
        <select name="loja" onchange="this.form.submit()">
            <option value="">— Todas —</option>
            <?php foreach ($lojas as $id => $nome): ?>
                <option value="<?= $id ?>" <?= $id == $lojaSelecionada ? 'selected' : '' ?>>
                    <?= $nome ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Item:</label>
        <select name="nome" onchange="this.form.submit()">
            <option value="">— Todos —</option>
            <?php foreach ($nomesDisponiveis as $nome): ?>
                <option value="<?= $nome ?>" <?= $nome === $nomeSelecionado ? 'selected' : '' ?>>
                    <?= $nome ?>
                </option>
            <?php endforeach; ?>
        </select>

        <a href="inventario_inativos.php" class="btn limpar-filtros">Limpar filtros</a>

    </form>

    <?php if (empty($listaInativos)): ?>
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
                <?php foreach ($listaInativos as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['controle']) ?></td>
                        <td><?= htmlspecialchars($item['nome']) ?></td>
                        <td><?= htmlspecialchars($item['nome_loja']) ?></td>
                        <td class="acoes">
                            <button type="button" class="acao detalhes" onclick="abrirDetalhes(<?= $item['id'] ?>)">🔍</button>
                            <button type="button" class="acao editar"
                                    onclick="abrirModalReativar(<?= $item['id'] ?>, '<?= htmlspecialchars($item['nome']) ?>')">♻️</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="acoes-gerais">
        <a class="btn" href="inventario.php">🔙 Voltar ao inventário</a>
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

<!-- Modal de reativação -->
<div id="modalReativar" class="modal">
    <div class="modal-content" style="max-width:400px;">
        <h3>♻️ Reativar Item</h3>
        <p id="textoReativar"></p>

        <form method="POST" action="reativar_item.php">
            <input type="hidden" name="id" id="reativarId">

            <label>Nova loja:</label>
            <select name="nova_loja" required>
                <?php foreach ($lojas as $id => $nome): ?>
                    <option value="<?= $id ?>"><?= $nome ?></option>
                <?php endforeach; ?>
            </select>

            <div class="modal-acoes">
                <button type="submit" class="btn confirmar">Reativar</button>
                <button type="button" class="btn cancelar" onclick="fecharModalReativar()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script src="/js/inventario_inativos.js?v=<?= time() ?>"></script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
