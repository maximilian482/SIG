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
// TIPO DO PROTOCOLO (VEM DO BOTÃO)
// ===============================
$tipo = trim($_GET['tipo'] ?? '');

$tiposValidos = ['documento', 'malote', 'item', 'nota', 'comprovante'];

if (!in_array($tipo, $tiposValidos)) {
    echo "<p>Tipo de protocolo inválido.</p>";
    exit;
}

// Buscar lista de todas as lojas
$todasLojas = $conn->query("
    SELECT id, nome FROM lojas ORDER BY nome
")->fetch_all(MYSQLI_ASSOC);

// Buscar lista de lojas destino (todas menos a origem)
$lojasDestino = $conn->query("
    SELECT id, nome FROM lojas 
    WHERE id <> $lojaOrigem
    ORDER BY nome
")->fetch_all(MYSQLI_ASSOC);

// Buscar funcionários ativos
$funcionarios = $conn->query("
    SELECT id, nome
    FROM funcionarios
    WHERE desligamento IS NULL 
    ORDER BY nome
")->fetch_all(MYSQLI_ASSOC);

$erro = "";

// ===============================
// PROCESSAR FORMULÁRIO
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $loja_origem   = intval($_POST['loja_origem'] ?? 0);
    $loja_destino  = intval($_POST['loja_destino'] ?? 0);
    $responsavel   = intval($_POST['responsavel_id'] ?? 0);
    $tipo          = trim($_POST['tipo'] ?? ''); // vem do hidden
    $descricao     = trim($_POST['descricao'] ?? '');
    $quantidade    = intval($_POST['quantidade'] ?? 1);
    $observacoes   = trim($_POST['observacoes'] ?? '');

    // ===============================
    // VALIDAÇÃO: origem ≠ destino
    // ===============================
    if ($loja_origem === $loja_destino) {
        $erro = "❌ A loja de origem e destino não podem ser iguais.";
    }

    if (!$loja_origem || !$loja_destino || !$responsavel || !$descricao) {
        $erro = "❌ Preencha todos os campos obrigatórios.";
    }

    if (!in_array($tipo, $tiposValidos)) {
        $erro = "❌ Tipo de protocolo inválido.";
    }

    if (empty($erro)) {

        // ===============================
        // GERAR PROTOCOLO — ROBUSTO
        // ===============================
        $res = $conn->query("SELECT protocolo FROM chamados_trilho ORDER BY id DESC LIMIT 1");

        if ($res->num_rows > 0) {
            $ultimo = $res->fetch_assoc()['protocolo'];
            $numero = intval(preg_replace('/\D/', '', $ultimo)) + 1;
        } else {
            $numero = 1;
        }

        $protocolo = 'CT' . str_pad($numero, 4, '0', STR_PAD_LEFT);

        // SALVAR PROTOCOLO
        // (QUANTIDADE SALVA NA DESCRIÇÃO, SEM DUPLICAR)

        // remove qualquer " — X unidade(s)" que já exista no final
        $descricaoLimpa = preg_replace('/\s—\s\d+\s+unidade[s]?$/', '', $descricao);

        $descricaoFinal = $descricaoLimpa . " — " . $quantidade . " unidade" . ($quantidade > 1 ? "s" : "");


        $stmt = $conn->prepare("
            INSERT INTO chamados_trilho (
                protocolo, tipo, loja_origem_id, loja_destino_id, solicitante_id,
                solicitado_id, descricao, observacoes, status, data_criacao
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'faturado', NOW())
        ");

        $stmt->bind_param(
            "ssiiiiss",
            $protocolo,
            $tipo,
            $loja_origem,
            $loja_destino,
            $usuarioId,
            $responsavel,
            $descricaoFinal,
            $observacoes
        );

        $stmt->execute();

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

<h2>📦 Novo Protocolo: <?= ucfirst($tipo) ?></h2>
<p>Preencha os dados abaixo para registrar este protocolo.</p>

<?php if (!empty($erro)): ?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            mostrarMensagem("<?= addslashes($erro) ?>", "erro");
        });
    </script>
<?php endif; ?>

<form method="POST" class="form-chamado">

    <!-- tipo oculto -->
    <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">

    <label for="loja_origem">Loja de Origem:</label>
    <select id="loja_origem" name="loja_origem" required>
        <?php foreach ($todasLojas as $l): ?>
            <option value="<?= $l['id'] ?>" <?= $l['id'] == $lojaOrigem ? 'selected' : '' ?>>
                <?= htmlspecialchars($l['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="loja_destino">Loja de Destino:</label>
    <select id="loja_destino" name="loja_destino" required>
        <option value="">— Selecione —</option>
        <?php foreach ($lojasDestino as $l): ?>
            <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['nome']) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="responsavel_id">Aos cuidados de:</label>
    <select id="responsavel_id" name="responsavel_id" required>
        <option value="">— Selecione —</option>
        <?php foreach ($funcionarios as $f): ?>
            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome']) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="descricao">Descrição do item:</label>
    <input type="text" id="descricao" name="descricao" required>

    <label for="quantidade">Quantidade:</label>
    <input type="number" id="quantidade" name="quantidade" min="1" value="1" required>

    <label for="observacoes">Observações (opcional):</label>
    <textarea id="observacoes" name="observacoes" rows="2"></textarea>

    <div class="botoes-acoes">
        <a class="btn-voltar" href="chamados_trilho.php">🔙 Voltar</a>
        <button type="submit" class="btn-submit">📨 Registrar Protocolo</button>
    </div>

</form>

</div>
</div>

<script src="/js/chamados_trilho_abrir_simples.js"></script>

<?php
echo ob_get_clean();
exit;
?>
