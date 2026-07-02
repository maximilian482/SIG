<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

$cpf = $_SESSION['cpf'] ?? '';

if (!$cpf) {
    echo "<h2 style='color:red; text-align:center;'>❌ Sessão expirada.</h2>";
    exit;
}

// Verifica permissão do setor de compras
if (!temAcesso($conn, $cpf, 'ferramentas_compras_externas')) {
    echo "<h2 style='color:red; text-align:center;'>❌ Você não tem permissão para finalizar compras.</h2>";
    exit;
}

// ID da compra
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo "<h2 style='color:red; text-align:center;'>❌ Solicitação inválida.</h2>";
    exit;
}

// Buscar dados da compra
$sql = "
    SELECT ce.*, f.nome AS solicitante_nome, l.nome AS loja_nome
    FROM compras_externas ce
    JOIN funcionarios f ON f.id = ce.solicitante_id
    JOIN lojas l ON l.id = ce.loja_id
    WHERE ce.id = $id
";

$res = $conn->query($sql);
$compra = $res->fetch_assoc();

if (!$compra) {
    echo "<h2 style='color:red; text-align:center;'>❌ Solicitação não encontrada.</h2>";
    exit;
}

// ===============================
// FINALIZAR COMPRA
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dataEntrega = date("Y-m-d H:i:s");
    $recebidoPor = trim($_POST['recebido_por']);

    if (empty($recebidoPor)) {
        $erro = "Informe quem recebeu o produto.";
    } else {

        $stmt = $conn->prepare("
            UPDATE compras_externas
            SET data_entrega = ?, status = 'concluido', observacoes = CONCAT(IFNULL(observacoes,''), '\nProduto recebido por: ', ?)
            WHERE id = ?
        ");

        $stmt->bind_param("ssi", $dataEntrega, $recebidoPor, $id);
        $stmt->execute();

        header("Location: compras_externas_detalhes.php?id=$id&finalizado=1");
        exit;
    }
}

// ===============================
// INÍCIO DO HTML
// ===============================
ob_start();
?>

<link rel="stylesheet" href="../css/chamados_setores.css">
<link rel="stylesheet" href="../css/compras_externas.css">

<h2>✔ Finalizar Compra Externa</h2>

<div class="box-detalhes">

    <p><strong>ID:</strong> #<?= $compra['id'] ?></p>
    <p><strong>Loja:</strong> <?= $compra['loja_nome'] ?></p>
    <p><strong>Solicitante:</strong> <?= $compra['solicitante_nome'] ?></p>
    <p><strong>Produto:</strong> <?= htmlspecialchars($compra['produto']) ?></p>
    <p><strong>Quantidade:</strong> <?= $compra['quantidade'] ?></p>

</div>

<hr>

<?php if (!empty($erro)): ?>
    <div class="erro-msg"><?= $erro ?></div>
<?php endif; ?>

<form method="POST" class="form-chamado">

    <label>Quem recebeu o produto? *</label>
    <input type="text" name="recebido_por" required>

    <button type="submit" class="btn-finalizar">
        ✔ Confirmar Entrega e Finalizar
    </button>

    <a href="compras_externas_detalhes.php?id=<?= $id ?>" class="btn-voltar">
        🔙 Voltar
    </a>

</form>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
