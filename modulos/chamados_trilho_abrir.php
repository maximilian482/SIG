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

// Buscar nome da loja de origem
$nomeLojaOrigem = $conn->query("SELECT nome FROM lojas WHERE id = $lojaOrigem")->fetch_assoc()['nome'] ?? '—';

// Buscar lista de todas as lojas (para permitir alterar a origem)
$todasLojas = $conn->query("
    SELECT id, nome FROM lojas ORDER BY nome
")->fetch_all(MYSQLI_ASSOC);

// Buscar lista de lojas solicitadas (todas menos a origem)
$lojasSolicitadas = $conn->query("
    SELECT id, nome FROM lojas 
    WHERE id <> $lojaOrigem
    ORDER BY nome
")->fetch_all(MYSQLI_ASSOC);

// Buscar lista de funcionários ativos (para o campo SOLICITADO PARA)
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
// PROCESSAR FORMULÁRIO
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $loja_origem     = intval($_POST['loja_origem'] ?? 0);
    $loja_solicitada = intval($_POST['loja_solicitada'] ?? 0);
    $solicitado_id   = intval($_POST['solicitado_id'] ?? 0);
    $observacao      = trim($_POST['item_observacao'] ?? '');

    // ITENS NO NOVO FORMATO
    $itens = $_POST['itens'] ?? [];

    // Validação de itens
    $temItemValido = false;
    $itensLimpos   = [];

    foreach ($itens as $key => $item) {
        $codigo = isset($item['codigo']) ? preg_replace('/[^0-9]/', '', $item['codigo']) : '';
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

    if (empty($erro)) {

        // ===============================
        // GERAR PROTOCOLO CT0001
        // ===============================
        $res = $conn->query("SELECT protocolo FROM chamados_trilho ORDER BY id DESC LIMIT 1");

        if ($res->num_rows > 0) {
            $ultimo = $res->fetch_assoc()['protocolo'];
            $numero = intval(substr($ultimo, 2)) + 1;
        } else {
            $numero = 1;
        }

        $protocolo = 'CT' . str_pad($numero, 4, '0', STR_PAD_LEFT);

        // Gerar título automático com base nos itens limpos
        $titulo = gerarTituloTrilho($itensLimpos);

        // ===============================
        // CRIAR REGISTRO DO TRILHO
        // ===============================
        $stmt = $conn->prepare("
            INSERT INTO chamados_trilho (
                protocolo, loja_origem_id, loja_destino_id, solicitante_id,
                solicitado_id, descricao, observacoes, status, data_criacao
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'aberto', NOW())
        ");

        $descricao = $titulo;

        $stmt->bind_param(
            "siiiiss",
            $protocolo,
            $loja_origem,
            $loja_solicitada,
            $usuarioId,
            $solicitado_id,
            $descricao,
            $observacao
        );

        $stmt->execute();
        $idTrilho = $stmt->insert_id;

        // ===============================
        // SALVAR ITENS
        // ===============================
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

        // ===============================
        // FINALIZAR
        // ===============================
        setFlash("success", "✔️ Protocolo criado com sucesso!");
        header("Location: chamados_trilho.php");
        exit;
    }
}

ob_start();
?>

<link rel="stylesheet" href="/css/chamados_trilho_abrir.css">

<div class="container-chamado">
    <div class="card-chamado">

<h2>🚚 Novo Protocolo do Trilho</h2>
<p>Preencha os dados abaixo para registrar uma entrega.</p>

<?php if (!empty($erro)): ?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            mostrarMensagem("<?= addslashes($erro) ?>", "erro");
        });
    </script>
<?php endif; ?>

<form method="POST" class="form-chamado">

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
