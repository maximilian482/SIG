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
$lojaUsuario = intval($_SESSION['loja'] ?? 0);

// ===============================
// TIPO DO PROTOCOLO (VEM DO MENU)
// ===============================
$tipo = trim($_GET['tipo'] ?? '');

$tiposValidos = [
    'remanejamento' => 'remanejamento',
    'malote'        => 'malote',
    'item'          => 'item'
];



if (!array_key_exists($tipo, $tiposValidos)) {
    echo "<p>Tipo de protocolo inválido.</p>";
    exit;
}

$tipoFinal = $tiposValidos[$tipo];

// ===============================
// BUSCAR LOJAS E FUNCIONÁRIOS
// ===============================
$todasLojas = $conn->query("
    SELECT id, nome FROM lojas ORDER BY nome
")->fetch_all(MYSQLI_ASSOC);

$funcionarios = $conn->query("
    SELECT id, nome FROM funcionarios
    WHERE desligamento IS NULL
    ORDER BY nome
")->fetch_all(MYSQLI_ASSOC);

$erro = "";

// ===============================
// PROCESSAR FORMULÁRIO
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao          = trim($_POST['acao'] ?? '');
    $tipoFinal     = trim($_POST['tipo'] ?? '');
    $descricao     = trim($_POST['descricao'] ?? '');
    $quantidade    = intval($_POST['quantidade'] ?? 1);
    $observacoes   = trim($_POST['observacoes'] ?? '');
    $lojaEscolhida = intval($_POST['loja_escolhida'] ?? 0);
    $responsavelId = intval($_POST['responsavel_id'] ?? 0);

    // ===============================
    // VALIDAÇÕES
    // ===============================
    if (!in_array($acao, ['enviar', 'receber'])) {
        $erro = "❌ Escolha se deseja ENVIAR ou RECEBER.";
    }

    if (!$descricao || !$lojaEscolhida) {
        $erro = "❌ Preencha todos os campos obrigatórios.";
    }

    // “Aos cuidados de” obrigatório SOMENTE quando ENVIAR
    if ($acao === 'enviar' && !$responsavelId) {
        $erro = "❌ Selecione o responsável na loja destino.";
    }

    // ===============================
    // DEFINIR ORIGEM E DESTINO
    // ===============================
    if (empty($erro)) {

        if ($acao === 'enviar') {
            $loja_origem  = $lojaUsuario;
            $loja_destino = $lojaEscolhida;
            $tituloAcao   = "Envio de";
        } else {
            $loja_origem  = $lojaEscolhida;
            $loja_destino = $lojaUsuario;
            $tituloAcao   = "Recebimento de";
        }

        if ($loja_origem === $loja_destino) {
            $erro = "❌ A loja de origem e destino não podem ser iguais.";
        }
    }

    // ===============================
    // SALVAR PROTOCOLO
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

        // DESCRIÇÃO FINAL
        $descricaoFinal = "$tituloAcao $descricao — $quantidade unidade" . ($quantidade > 1 ? "s" : "");

        // INSERT
        $stmt = $conn->prepare("
            INSERT INTO chamados_trilho (
                protocolo, tipo, acao, loja_origem_id, loja_destino_id,
                solicitante_id, solicitado_id,
                descricao, observacoes, status, data_criacao
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'faturado', NOW())
        ");


        if (!$stmt) {
            die("Erro no prepare: " . $conn->error);
        }

        $stmt->bind_param(
            "sssiiiiss",
            $protocolo,
            $tipoFinal,
            $acao,            // <-- AQUI
            $loja_origem,
            $loja_destino,
            $usuarioId,
            $responsavelId,
            $descricaoFinal,
            $observacoes
        );


        if (!$stmt->execute()) {
            die("Erro ao salvar protocolo: " . $stmt->error);
        }

        setFlash("success", "✔️ Protocolo criado com sucesso!");
        header("Location: chamados_trilho.php");
        exit;
    }
}

ob_start();
?>

<link rel="stylesheet" href="/css/chamados_trilho_abrir_simples.css">

<div class="container-chamado">
    <div class="card-chamado">

<h2>📦 Novo Protocolo: <?= ucfirst($tipoFinal) ?></h2>
<p>Preencha os dados abaixo para registrar este protocolo.</p>

<?php if (!empty($erro)): ?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            mostrarMensagem("<?= addslashes($erro) ?>", "erro");
        });
    </script>
<?php endif; ?>

<form method="POST" class="form-chamado">

    <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipoFinal) ?>">

    <label>Você deseja:</label>
    <div class="radio-group">
        <label><input type="radio" name="acao" value="enviar" required> 📤 Enviar</label>
        <label><input type="radio" name="acao" value="receber" required> 📥 Receber</label>
    </div>

    <label for="loja_escolhida">Selecione a outra loja:</label>
    <select id="loja_escolhida" name="loja_escolhida" required>
        <option value="">— Selecione —</option>
        <?php foreach ($todasLojas as $l): ?>
            <option value="<?= $l['id'] ?>">
                <?= htmlspecialchars($l['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <div id="avisoLojaIgual" class="aviso-loja-igual">
        ⚠️ Você está cadastrado nesta loja. Por isso não é possível enviar ou receber para ela.
    </div>

    <label for="responsavel_id">Aos cuidados de:</label>
    <select id="responsavel_id" name="responsavel_id">
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

<?php
echo ob_get_clean();
?>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const lojaUsuario = <?= $lojaUsuario ?>;
    const selectLoja = document.getElementById("loja_escolhida");
    const aviso = document.getElementById("avisoLojaIgual");

    selectLoja.addEventListener("change", () => {
        if (parseInt(selectLoja.value) === lojaUsuario) {
            aviso.style.display = "block";
        } else {
            aviso.style.display = "none";
        }
    });

});
</script>
