<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

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

    // Buscar último número sequencial
    $sql = "SELECT MAX(numero_sequencial) AS ultimo FROM inventario";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();
    $novoNumero = intval($row['ultimo']) + 1;

    // Formatar número
    $numeroFormatado = str_pad($novoNumero, 3, '0', STR_PAD_LEFT);

    // Buscar sigla
    $sigla = $categorias[$categoria_id]['sigla'] ?? 'SEM';

    // Patrimônio final
    $codigoPatrimonioVisual = $sigla . $numeroFormatado;

    // Salvar
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
        $mensagemSucesso = "Item adicionado com sucesso! Patrimônio: $codigoPatrimonioVisual";
    } else {
        $mensagemErro = "Erro ao salvar: " . $stmt->error;
    }
}

/* ============================
   INÍCIO DO HTML (layout)
============================ */
ob_start();
?>

<link rel="stylesheet" href="../css/inventarioform.css">

<h2>➕ Adicionar novo item ao inventário</h2>

<?php if (!empty($mensagemSucesso)): ?>
    <p class="msg-sucesso">✅ <?= $mensagemSucesso ?></p>
<?php endif; ?>

<?php if (!empty($mensagemErro)): ?>
    <p class="msg-erro">❌ <?= $mensagemErro ?></p>
<?php endif; ?>

<form method="POST" class="form-inventario">

    <label>Loja:</label>
    <select name="loja_id" required>
        <option value="">— Selecione —</option>
        <?php foreach ($lojas as $id => $nome): ?>
            <option value="<?= $id ?>"><?= htmlspecialchars($nome) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Categoria:</label>
    <select name="categoria_id" required>
        <option value="">— Selecione —</option>
        <?php foreach ($categorias as $id => $cat): ?>
            <option value="<?= $id ?>"><?= htmlspecialchars($cat['nome']) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Patrimônio:</label>
    <input type="text" value="Será gerado automaticamente" readonly class="campo-readonly">

    <label>Nome do item:</label>
    <div class="linha-flex">
        <select name="nome" id="nome" onchange="atualizarValor()">
            <?php foreach (array_keys($valoresPadrao) as $n): ?>
                <option value="<?= htmlspecialchars($n) ?>"><?= htmlspecialchars($n) ?></option>
            <?php endforeach; ?>
            <option value="Outro">Outro</option>
        </select>

        <button type="button" class="btn-add" onclick="abrirModalNovoItem()">+</button>
    </div>

    <label>Descrição:</label>
    <input type="text" name="descricao">

    <label>Setor:</label>
    <div class="linha-flex">
        <select name="setor" id="setor">
            <?php foreach (['Caixa','Balcão','Depósito','Gerência','Externo','Escritório','Perfumaria'] as $setor): ?>
                <option value="<?= htmlspecialchars($setor) ?>"><?= htmlspecialchars($setor) ?></option>
            <?php endforeach; ?>
            <option value="Outro">Outro</option>
        </select>

        <button type="button" class="btn-add" onclick="abrirModalNovoSetor()">+</button>
    </div>

    <label>Responsável:</label>
    <select name="responsavel_id">
        <option value="<?= $ID_GESTOR ?>" selected>Gestor</option>
        <?php foreach ($funcionarios as $id => $nome): ?>
            <option value="<?= $id ?>"><?= htmlspecialchars($nome) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Valor (R$):</label>
    <input type="number" step="0.01" name="valor" id="valor">

    <button type="submit" class="btn-salvar">Salvar</button>
</form>

<a class="btn-voltar" href="inventario.php">🔙 Voltar ao inventário</a>

<!-- Modal Novo Item -->
<div id="modalNovoItem" class="modal">
    <div class="modal-content">
        <h3>➕ Novo Item</h3>

        <label>Nome do item:</label>
        <input type="text" id="novoItemNome">

        <label>Valor sugerido (R$):</label>
        <input type="number" id="novoItemValor" step="0.01">

        <div class="modal-acoes">
            <button class="btn confirmar" onclick="salvarNovoItem()">Salvar</button>
            <button class="btn cancelar" onclick="fecharModalNovoItem()">Cancelar</button>
        </div>
    </div>
</div>

<!-- Modal Novo Setor -->
<div id="modalNovoSetor" class="modal">
    <div class="modal-content">
        <h3>➕ Novo Setor</h3>

        <label>Nome do setor:</label>
        <input type="text" id="novoSetorNome">

        <div class="modal-acoes">
            <button class="btn confirmar" onclick="salvarNovoSetor()">Salvar</button>
            <button class="btn cancelar" onclick="fecharModalNovoSetor()">Cancelar</button>
        </div>
    </div>
</div>

<script>
window.valoresPadrao = <?= json_encode($valoresPadrao) ?>;
</script>

<script src="/js/inventario_adicionar.js?v=<?= time() ?>"></script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
