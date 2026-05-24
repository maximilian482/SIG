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
// VALIDAR ID
// ===============================
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlash("error", "Protocolo inválido.");
    header("Location: chamados_trilho.php");
    exit;
}

// ===============================
// BUSCAR PROTOCOLO
// ===============================
$stmt = $conn->prepare("
    SELECT *
    FROM chamados_trilho
    WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$dados = $stmt->get_result()->fetch_assoc();

if (!$dados) {
    setFlash("error", "Protocolo não encontrado.");
    header("Location: chamados_trilho.php");
    exit;
}

// ===============================
// VALIDAR PERMISSÃO
// ===============================
if ($dados['solicitante_id'] != $usuarioId) {
    setFlash("error", "Você não tem permissão para editar este protocolo.");
    header("Location: chamados_trilho.php");
    exit;
}

// ===============================
// VALIDAR TIPO SIMPLES
// ===============================
$tiposSimples = ['Remanejamento', 'Malote', 'Item'];

if (!in_array($dados['tipo'], $tiposSimples)) {
    setFlash("error", "Este formulário é apenas para protocolos simples.");
    header("Location: chamados_trilho.php");
    exit;
}

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

// ===============================
// EXTRAIR DESCRIÇÃO E QUANTIDADE
// ===============================
$descricaoOriginal = $dados['descricao'];
$descricaoLimpa = $descricaoOriginal;
$quantidadeExtraida = 1;

if (preg_match('/^(.*)\s—\s(\d+)\s+unidade[s]?$/', $descricaoOriginal, $m)) {
    $descricaoLimpa = trim($m[1]);
    $quantidadeExtraida = intval($m[2]);
}

// ===============================
// PROCESSAR FORMULÁRIO
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao          = trim($_POST['acao'] ?? '');
    $descricao     = trim($_POST['descricao'] ?? '');
    $quantidade    = intval($_POST['quantidade'] ?? 1);
    $observacoes   = trim($_POST['observacoes'] ?? '');
    $lojaEscolhida = intval($_POST['loja_escolhida'] ?? 0);
    $responsavelId = intval($_POST['responsavel_id'] ?? 0);

    // ===============================
    // VALIDAÇÕES
    // ===============================
    if (!in_array($acao, ['enviar', 'receber'])) {
        setFlash("error", "❌ Escolha se deseja ENVIAR ou RECEBER.");
        header("Location: chamados_trilho_editar_simples.php?id={$id}");
        exit;
    }

    if (!$descricao || !$lojaEscolhida) {
        setFlash("error", "❌ Preencha todos os campos obrigatórios.");
        header("Location: chamados_trilho_editar_simples.php?id={$id}");
        exit;
    }

    if ($acao === 'enviar' && !$responsavelId) {
        setFlash("error", "❌ Selecione o responsável na loja destino.");
        header("Location: chamados_trilho_editar_simples.php?id={$id}");
        exit;
    }

    // ===============================
    // DEFINIR ORIGEM E DESTINO
    // ===============================
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
        setFlash("error", "❌ A loja de origem e destino não podem ser iguais.");
        header("Location: chamados_trilho_editar_simples.php?id={$id}");
        exit;
    }

    // ===============================
    // MONTAR DESCRIÇÃO FINAL
    // ===============================
    $descricaoFinal = "$tituloAcao $descricao — $quantidade unidade" . ($quantidade > 1 ? "s" : "");

    // ===============================
    // ATUALIZAR PROTOCOLO
    // ===============================
    $stmt = $conn->prepare("
        UPDATE chamados_trilho
        SET acao = ?, loja_origem_id = ?, loja_destino_id = ?, solicitado_id = ?,
            descricao = ?, observacoes = ?
        WHERE id = ? AND solicitante_id = ?
    ");

    $stmt->bind_param(
        "siiissii",
        $acao,
        $loja_origem,
        $loja_destino,
        $responsavelId,
        $descricaoFinal,
        $observacoes,
        $id,
        $usuarioId
    );

    $stmt->execute();

    setFlash("success", "✔️ Protocolo atualizado com sucesso!");
    header("Location: chamados_trilho.php");
    exit;
}

?>

<link rel="stylesheet" href="/css/chamados_trilho_abrir_simples.css">

<div class="container-chamado">
    <div class="card-chamado">

<h2>✏️ Editar Protocolo: <?= htmlspecialchars($dados['tipo']) ?></h2>
<p>Altere os dados abaixo e salve o protocolo.</p>

<form method="POST" class="form-chamado">

    <label>Você deseja:</label>
    <div class="radio-group">
        <label><input type="radio" name="acao" value="enviar" <?= $dados['acao'] === 'enviar' ? 'checked' : '' ?>> 📤 Enviar</label>
        <label><input type="radio" name="acao" value="receber" <?= $dados['acao'] === 'receber' ? 'checked' : '' ?>> 📥 Receber</label>
    </div>

    <label for="loja_escolhida">Selecione a outra loja:</label>
    <select id="loja_escolhida" name="loja_escolhida" required>
        <option value="">— Selecione —</option>
        <?php foreach ($todasLojas as $l): ?>
            <option value="<?= $l['id'] ?>" <?= ($l['id'] == $dados['loja_origem_id'] || $l['id'] == $dados['loja_destino_id']) && $l['id'] != $lojaUsuario ? 'selected' : '' ?>>
                <?= htmlspecialchars($l['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="responsavel_id">Aos cuidados de:</label>
    <select id="responsavel_id" name="responsavel_id">
        <option value="">— Selecione —</option>
        <?php foreach ($funcionarios as $f): ?>
            <option value="<?= $f['id'] ?>" <?= $f['id'] == $dados['solicitado_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($f['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="descricao">Descrição do item:</label>
    <input type="text" id="descricao" name="descricao"
           value="<?= htmlspecialchars($descricaoLimpa) ?>" required>

    <label for="quantidade">Quantidade:</label>
    <input type="number" id="quantidade" name="quantidade"
           value="<?= $quantidadeExtraida ?>" min="1" required>

    <label for="observacoes">Observações (opcional):</label>
    <textarea id="observacoes" name="observacoes" rows="2"><?= htmlspecialchars($dados['observacoes']) ?></textarea>

    <div class="botoes-acoes">
        <a class="btn-voltar" href="chamados_trilho.php">🔙 Voltar</a>
        <button type="submit" class="btn-submit">💾 Salvar Alterações</button>
    </div>

</form>

</div>
</div>
