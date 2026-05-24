<?php
session_start();
require_once '../includes/funcoes.php';
require_once __DIR__ . '/../config/bootstrap.php';
$conn = conectar();

// ===============================
// VERIFICA LOGIN
// ===============================
if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

$usuarioId   = intval($_SESSION['funcionario_id'] ?? 0);
$nomeUsuario = $_SESSION['nome'] ?? '';
$lojaOrigem  = intval($_SESSION['loja'] ?? 0);

// ===============================
// CAPTURAR TIPO (GET → POST)
// ===============================

// 1. Primeiro tenta pegar do GET (quando vem do modal)
$tipo = $_GET['tipo'] ?? null;

// 2. Se for POST, pega do POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? $tipo;
}

// 3. Se ainda estiver vazio, define medicamento
if (!$tipo) {
    $tipo = 'medicamento';
}

// ===============================
// BUSCAR DADOS
// ===============================
$nomeLojaOrigem = $conn->query("SELECT nome FROM lojas WHERE id = $lojaOrigem")->fetch_assoc()['nome'] ?? '—';

$todasLojas = $conn->query("SELECT id, nome FROM lojas ORDER BY nome")->fetch_all(MYSQLI_ASSOC);

$lojasSolicitadas = $conn->query("
    SELECT id, nome FROM lojas 
    WHERE id <> $lojaOrigem
    ORDER BY nome
")->fetch_all(MYSQLI_ASSOC);

$funcionarios = $conn->query("
    SELECT f.id, f.nome
    FROM funcionarios f
    LEFT JOIN cargos c ON c.id = f.cargo_id
    WHERE 
        (f.desligamento IS NULL OR f.desligamento = '' OR f.desligamento = '0000-00-00')
        AND f.eh_funcionario = 1
        AND c.nome_cargo NOT IN (
            'CEO', 'MOTOBOY', 'SUPER', 'COMPRADOR', 'MANUTENCAO', 'CONTADOR',
            'TI', 'LOCUTOR', 'DP', 'RH'
        )
    ORDER BY f.nome
")->fetch_all(MYSQLI_ASSOC);

$erro = "";

// ===============================
// TIPO DO PROTOCOLO (VEM DO MENU)
// ===============================
$tiposValidos = [
    'medicamento' => 'medicamento',
    'perfumaria'  => 'perfumaria'
];

$tipo = $_GET['tipo'] ?? '';

if (!in_array($tipo, ['medicamento', 'perfumaria'])) {
    header("Location: /modulos/chamados_trilho_abrir_simples.php?tipo=$tipo");
    exit;
}


// ===============================
// PROCESSAR FORMULÁRIO
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $loja_origem     = intval($_POST['loja_origem'] ?? 0);
    $loja_solicitada = intval($_POST['loja_solicitada'] ?? 0);
    $solicitado_id   = intval($_POST['solicitado_id'] ?? 0);
    $observacao      = trim($_POST['item_observacao'] ?? '');

    // ===============================
    // VALIDAÇÃO
    // ===============================
    if ($loja_origem === $loja_solicitada) {
        $erro = "❌ A loja de origem e a loja de destino não podem ser iguais.";
    }

    $itens = $_POST['itens'] ?? [];
    $temItemValido = false;
    $itensLimpos   = [];

    foreach ($itens as $item) {
        $codigo = preg_replace('/[^0-9]/', '', $item['codigo'] ?? '');
        $nome   = trim($item['descricao'] ?? '');
        $qtd    = intval($item['quantidade'] ?? 0);

        if ($codigo && $nome && $qtd > 0) {
            $temItemValido = true;
            $itensLimpos[] = [
                'codigo'     => $codigo,
                'descricao'  => $nome,
                'quantidade' => $qtd
            ];
        }
    }

    if (!$loja_origem || !$loja_solicitada || !$solicitado_id || !$temItemValido) {
        $erro = "❌ Preencha todos os campos obrigatórios corretamente.";
    }

    // ===============================
    // SALVAR NO BANCO
    // ===============================
    if (empty($erro)) {

        // GERAR PROTOCOLO
        $res = $conn->query("SELECT protocolo FROM chamados_trilho ORDER BY id DESC LIMIT 1");
        if ($res->num_rows > 0) {
            $ultimo = $res->fetch_assoc()['protocolo'];
            $numero = intval(preg_replace('/\D/', '', $ultimo)) + 1;
        } else {
            $numero = 1;
        }
        $protocolo = 'CT' . str_pad($numero, 4, '0', STR_PAD_LEFT);

        // TÍTULO AUTOMÁTICO
        $titulo = gerarTituloTrilho($itensLimpos);

        // INSERT PRINCIPAL
        $stmt = $conn->prepare("
            INSERT INTO chamados_trilho (
                protocolo, tipo, loja_origem_id, loja_destino_id, solicitante_id,
                solicitado_id, descricao, observacoes, status, data_criacao
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'aberto', NOW())
        ");

        $stmt->bind_param(
            "ssiiisss",
            $protocolo,
            $tipo,
            $loja_origem,
            $loja_solicitada,
            $usuarioId,
            $solicitado_id,
            $titulo,
            $observacao
        );

        $stmt->execute();
        $idTrilho = $stmt->insert_id;

        // SALVAR ITENS
        $stmt2 = $conn->prepare("
            INSERT INTO trilho_itens (trilho_id, codigo, descricao, quantidade)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($itensLimpos as $item) {
            $stmt2->bind_param(
                "issi",
                $idTrilho,
                $item['codigo'],
                $item['descricao'],
                $item['quantidade']
            );
            $stmt2->execute();
        }

        setFlash("success", "✔️ Protocolo criado com sucesso!");
        header("Location: chamados_trilho.php");
        exit;
    }
}

ob_start();
include ROOT_PATH . '/includes/flash.php';
?>

<link rel="stylesheet" href="/css/chamados_trilho_abrir.css">

<div class="container-chamado">
    <div class="card-chamado">

<h2>🚚 Novo Protocolo do Trilho</h2>
<p>Preencha os dados abaixo para registrar uma entrega.</p>

<form method="POST" class="form-chamado">

    <!-- TIPO DO TRILHO -->
    <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">

    <label for="loja_origem">Loja Solicitante:</label>
    <select id="loja_origem" name="loja_origem" required>
        <?php foreach ($todasLojas as $l): ?>
            <option value="<?= $l['id'] ?>" <?= $l['id'] == $lojaOrigem ? 'selected' : '' ?>>
                <?= htmlspecialchars($l['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Solicitante:</label>
    <input type="text" value="<?= htmlspecialchars($nomeUsuario) ?>" disabled>

    <label for="loja_solicitada">Loja de Liberação:</label>
    <select id="loja_solicitada" name="loja_solicitada" required>
        <option value="">— Selecione —</option>
        <?php foreach ($lojasSolicitadas as $l): ?>
            <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['nome']) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="solicitado_id">Solicitado para:</label>
    <select id="solicitado_id" name="solicitado_id" required>
        <option value="">— Selecione —</option>
        <?php foreach ($funcionarios as $f): ?>
            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome']) ?></option>
        <?php endforeach; ?>
    </select>

    <h3>📦 Itens</h3>

    <div id="lista-itens">
        <div class="item-bloco">
            <?php $idTemp = "novo_" . time(); ?>

            <label>Código interno:</label>
            <input type="text" name="itens[<?= $idTemp ?>][codigo]" required
                   pattern="[0-9]*" inputmode="numeric"
                   oninput="this.value = this.value.replace(/[^0-9]/g, '')">

            <label>Nome do item:</label>
            <input type="text" name="itens[<?= $idTemp ?>][descricao]" required>

            <label>Quantidade:</label>
            <input type="number" name="itens[<?= $idTemp ?>][quantidade]" min="1" value="1" required>

            <button type="button" class="btn-remover-item" onclick="this.parentElement.remove()">🗑 Remover</button>
        </div>
    </div>

    <button type="button" class="btn-add-item" id="btn-add-item">➕ Adicionar outro item</button>

    <label>Observação (opcional):</label>
    <textarea name="item_observacao" rows="2"></textarea>

    <div class="botoes-acoes">
        <a class="btn-voltar" href="chamados_trilho.php">🔙 Voltar</a>
        <button type="submit" class="btn-submit">📨 Registrar Protocolo</button>
    </div>

</form>

</div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const lista = document.getElementById("lista-itens");
    const btnAdd = document.getElementById("btn-add-item");

    btnAdd.addEventListener("click", () => {

        const idTemp = "novo_" + Date.now();

        const bloco = document.createElement("div");
        bloco.classList.add("item-bloco");

        bloco.innerHTML = `
            <label>Código interno:</label>
            <input type="text" name="itens[${idTemp}][codigo]" required
                   pattern="[0-9]*" inputmode="numeric"
                   oninput="this.value = this.value.replace(/[^0-9]/g, '')">

            <label>Nome do item:</label>
            <input type="text" name="itens[${idTemp}][descricao]" required>

            <label>Quantidade:</label>
            <input type="number" name="itens[${idTemp}][quantidade]" min="1" value="1" required>

            <button type="button" class="btn-remover-item" onclick="this.parentElement.remove()">🗑 Remover</button>
        `;

        lista.appendChild(bloco);
    });

});
</script>

<?php
echo ob_get_clean();
exit;
?>
