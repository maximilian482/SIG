<?php
session_start();

require_once '../includes/funcoes.php';
require_once __DIR__ . '/../config/bootstrap.php';

$conn = conectar();

// ===============================
// VALIDAR LOGIN
// ===============================
if (!isset($_SESSION['funcionario_id'])) {
    setFlash("error", "Sessão expirada. Faça login novamente.");
    header("Location: ../login.php");
    exit;
}

$usuarioId = intval($_SESSION['funcionario_id']);

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
$sql = "
    SELECT *
    FROM chamados_trilho
    WHERE id = {$id}
";

$dados = $conn->query($sql)->fetch_assoc();

if (!$dados) {
    setFlash("error", "Protocolo não encontrado.");
    header("Location: chamados_trilho.php");
    exit;
}

// ===============================
// VALIDAR TIPO
// ===============================
if ($dados['tipo'] === 'medicamento') {
    setFlash("error", "Este formulário é apenas para protocolos simples.");
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
// EXTRAIR DESCRIÇÃO E QUANTIDADE
// ===============================
$descricaoOriginal = $dados['descricao'];
$descricaoLimpa = $descricaoOriginal;
$quantidadeExtraida = 1;

// Detecta padrão: " — X unidade(s)" no final
if (preg_match('/^(.*)\s—\s(\d+)\s+unidade[s]?$/', $descricaoOriginal, $m)) {
    $descricaoLimpa = trim($m[1]);      // texto sem quantidade
    $quantidadeExtraida = intval($m[2]); // quantidade original
}

// ===============================
// PROCESSAR FORMULÁRIO
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $descricao   = trim($_POST['descricao'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $quantidade  = intval($_POST['quantidade'] ?? 1);

    if (empty($descricao)) {
        setFlash("error", "A descrição é obrigatória.");
        header("Location: chamados_trilho_editar_simples.php?id={$id}");
        exit;
    }

    // ===============================
    // LIMPAR QUANTIDADE ANTIGA
    // ===============================
    $descricaoLimpa = preg_replace('/\s—\s\d+\s+unidade[s]?$/', '', $descricao);

    // ===============================
    // MONTAR DESCRIÇÃO FINAL
    // ===============================
    $descricaoFinal = $descricaoLimpa . " — " . $quantidade . " unidade" . ($quantidade > 1 ? "s" : "");

    $stmt = $conn->prepare("
        UPDATE chamados_trilho
        SET descricao = ?, observacoes = ?
        WHERE id = ?
    ");

    $stmt->bind_param("ssi", $descricaoFinal, $observacoes, $id);
    $stmt->execute();

    setFlash("success", "✔️ Protocolo atualizado com sucesso!");
    header("Location: chamados_trilho.php");
    exit;
}

?>

<link rel="stylesheet" href="/css/chamados_trilho_abrir.css">

<div class="container-chamado">
    <div class="card-chamado">

        <h2>✏️ Editar Protocolo Simples</h2>
        <p>Altere os dados abaixo e salve o protocolo.</p>

        <form method="POST" class="form-chamado">

            <label for="descricao">Descrição do item:</label>
            <input type="text" id="descricao" name="descricao"
                   value="<?= htmlspecialchars($descricaoLimpa) ?>" required>

            <label for="quantidade">Quantidade:</label>
            <input type="number" id="quantidade" name="quantidade"
                   value="<?= $quantidadeExtraida ?>" min="1" required>

            <label for="observacoes">Observações:</label>
            <textarea id="observacoes" name="observacoes" rows="2"><?= htmlspecialchars($dados['observacoes']) ?></textarea>

            <div class="botoes-acoes">
                <a class="btn-voltar" href="chamados_trilho.php">🔙 Voltar</a>
                <button type="submit" class="btn-submit">💾 Salvar Alterações</button>
            </div>

        </form>

    </div>
</div>
