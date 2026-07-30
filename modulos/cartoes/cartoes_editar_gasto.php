<?php
session_start();

require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../dados/conexao.php';

$conn = conectar();

$cpf = $_SESSION['cpf'] ?? '';

if (!$cpf) {
    header("Location: ../../login.php");
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    echo "<h2 style='color:red;text-align:center;'>Gasto inválido.</h2>";
    exit;
}

// Buscar o gasto pelo ID
$stmt = $conn->prepare("
    SELECT *
    FROM cartoes_gastos
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$gasto = $stmt->get_result()->fetch_assoc();

if (!$gasto) {
    echo "<h2 style='color:red;text-align:center;'>Gasto não encontrado.</h2>";
    exit;
}

// Buscar todas as parcelas da mesma compra
$stmtParcelas = $conn->prepare("
    SELECT *
    FROM cartoes_gastos
    WHERE id_compra = ?
    ORDER BY parcelas ASC
");
$stmtParcelas->bind_param("i", $gasto['id_compra']);
$stmtParcelas->execute();
$parcelas = $stmtParcelas->get_result();

// Quantidade total de parcelas
$totalParcelas = $parcelas->num_rows;

// Valor total da compra (valor_parcela * totalParcelas)
$valorTotal = $totalParcelas * floatval($gasto['valor_parcela']);

ob_start();
?>

<link rel="stylesheet" href="/css/cartoes.css">

<div class="cartoes-modulo-funcionario" style="max-width: 700px; margin: 0 auto;">

    <h1 class="mb-3">✏ Editar Gasto</h1>
    <p>Você está editando um gasto no cartão <strong><?= $gasto['codigo_cartao'] ?></strong> (Ciclo #<?= $gasto['id_ciclo'] ?>).</p>

    <div class="cartoes-card">

        <form method="POST" action="cartoes_editar_gasto_salvar.php">

            <!-- Identificadores essenciais -->
            <input type="hidden" name="id_compra" value="<?= $gasto['id_compra'] ?>">
            <input type="hidden" name="id_ciclo" value="<?= $gasto['id_ciclo'] ?>">
            <input type="hidden" name="cpf_funcionario" value="<?= $gasto['cpf_funcionario'] ?>">
            <input type="hidden" name="codigo_cartao" value="<?= $gasto['codigo_cartao'] ?>">
            <input type="hidden" name="id_atribuicao" value="<?= $gasto['id_atribuicao'] ?>">

            <label class="form-label">Data da Compra</label>
            <input type="date" name="data_compra" class="form-control"
                   value="<?= $gasto['data_compra'] ?>" required>

            <label class="form-label mt-3">Descrição</label>
            <input type="text" name="descricao" class="form-control"
                   value="<?= $gasto['descricao'] ?>" required>

            <label class="form-label mt-3">Valor Total da Compra</label>
            <input type="number" step="0.01" name="valor_total" class="form-control"
                   value="<?= number_format($valorTotal, 2, '.', '') ?>" required>

            <label class="form-label mt-3">Quantidade de Parcelas</label>
            <input type="number" name="parcelas" class="form-control"
                   value="<?= $totalParcelas ?>" min="1" required>

            <button class="btn btn-novo mt-4 w-100">Salvar Alterações</button>

        </form>

        <a href="cartoes_funcionario.php" class="btn btn-secondary w-100 mt-3">
            ⬅ Voltar
        </a>

    </div>

</div>

<?php
$conteudo = ob_get_clean();
include __DIR__ . '/../../includes/layout.php';
?>
