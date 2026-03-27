<?php
session_start();

require_once '../includes/funcoes.php';
require_once __DIR__ . '/../config/bootstrap.php';
$conn = conectar();

// ===============================
// VALIDAR LOGIN
// ===============================
if (!isset($_SESSION['cpf'])) {
    header("Location: /login.php");
    exit;
}

// ===============================
// VALIDAR ID
// ===============================
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlash("erro", "Protocolo inválido.");
    header("Location: chamados_trilho.php");
    exit;
}

// ===============================
// BUSCAR DADOS DO PROTOCOLO
// ===============================
$sql = "
    SELECT 
        t.id,
        t.protocolo,
        t.solicitado_id,
        t.status,
        t.descricao,
        lo.nome AS loja_origem,
        ld.nome AS loja_destino
    FROM chamados_trilho t
    LEFT JOIN lojas lo ON lo.id = t.loja_origem_id
    LEFT JOIN lojas ld ON ld.id = t.loja_destino_id
    WHERE t.id = {$id}
";

$dados = $conn->query($sql)->fetch_assoc();

if (!$dados) {
    setFlash("erro", "Protocolo não encontrado.");
    header("Location: chamados_trilho.php");
    exit;
}

// ===============================
// VALIDAR STATUS
// ===============================
$status = strtolower(trim($dados['status'] ?? ''));

if ($status !== 'aberto') {
    setFlash("erro", "Este protocolo não está mais em status 'aberto'.");
    header("Location: chamados_trilho.php");
    exit;
}

// ===============================
// PROCESSAR FORMULÁRIO
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nota = trim($_POST['nota_transferencia'] ?? '');

    if (empty($nota)) {
        setFlash("erro", "Informe o número da nota de transferência.");
        header("Location: chamados_trilho_faturar.php?id={$id}");
        exit;
    }

    // Registrar quem faturou
    $funcionarioId = intval($_SESSION['funcionario_id']);

    // Atualizar protocolo
    $stmt = $conn->prepare("
        UPDATE chamados_trilho
        SET nota_transferencia = ?, 
            status = 'faturado',
            data_faturamento = NOW(),
            faturado_por = ?
        WHERE id = ?
    ");

    $stmt->bind_param("sii", $nota, $funcionarioId, $id);
    $stmt->execute();

    setFlash("success", "Protocolo faturado com sucesso!");
    header("Location: chamados_trilho.php");
    exit;
}

?>

<link rel="stylesheet" href="/css/chamados_trilho_faturar.css">

<div class="container-trilho">

    <h2>📄 Faturar Protocolo</h2>
    <p>Preencha o número da nota para faturar este protocolo.</p>

    <div class="card-trilho" style="max-width:500px; margin:auto;">

        <div class="card-produto"><?= htmlspecialchars($dados['descricao']) ?></div>

        <p><strong>Protocolo:</strong> <?= htmlspecialchars($dados['protocolo']) ?></p>
        <p><strong>Origem:</strong> <?= htmlspecialchars($dados['loja_origem']) ?></p>
        <p><strong>Destino:</strong> <?= htmlspecialchars($dados['loja_destino']) ?></p>

        <hr>

        <form method="POST">

            <label><strong>Número da nota de transferência:</strong></label>
            <input type="text" name="nota_transferencia" class="input-trilho" required>

            <div class="card-actions" style="margin-top:20px;">
                <a href="chamados_trilho.php" class="btn-trilho btn-detalhes">Cancelar</a>
                <button type="submit" class="btn-trilho btn-faturar">Faturar</button>
            </div>

        </form>

    </div>

</div>
