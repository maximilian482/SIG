<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';
$conn = conectar();

// Dados do funcionário logado
$cpf  = $_SESSION['cpf'] ?? '';
$nome = $_SESSION['usuario'] ?? '';

if (!$cpf) {
    header("Location: ../../login.php");
    exit;
}

// Busca cartões atribuídos ao funcionário + ciclo
$sql = "
    SELECT c.*, a.id AS id_atribuicao, a.data_atribuicao, a.id_ciclo, a.saldo_entregue
    FROM cartoes_atribuicoes a
    JOIN cartoes c ON c.codigo_cartao = a.codigo_cartao
    WHERE a.cpf_funcionario = ?
      AND a.ativo = 1
    ORDER BY c.codigo_cartao ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $cpf);
$stmt->execute();
$cartoes = $stmt->get_result();

ob_start();
?>

<link rel="stylesheet" href="/css/cartoes.css">

<div class="cartoes-modulo-funcionario">

<h1 class="mb-3">💳 Meus Cartões Corporativos</h1>
<p>Olá <strong><?= htmlspecialchars($nome) ?></strong>, aqui estão os cartões atribuídos a você.</p>

<?php if ($cartoes->num_rows == 0): ?>
    <div class="alert alert-info">
        Nenhum cartão foi atribuído a você.
    </div>
<?php endif; ?>

<div class="cartoes-grid">

<?php while ($c = $cartoes->fetch_assoc()): ?>

    <?php
        // Buscar total de gastos do ciclo (AGORA SOMENTE valor_parcela)
        $stmtG = $conn->prepare("
            SELECT SUM(valor_parcela) AS total_gastos
            FROM cartoes_gastos
            WHERE id_ciclo = ?
        ");
        $stmtG->bind_param("i", $c['id_ciclo']);
        $stmtG->execute();
        $totalGastos = floatval($stmtG->get_result()->fetch_assoc()['total_gastos'] ?? 0);

        // Saldo esperado no ciclo
        $saldoEsperado = $c['saldo_entregue'] - $totalGastos;
    ?>

    <div class="cartao-box">

        <div class="logo-banco"><?= $c['banco'] ?></div>

        <div class="chip"></div>

        <h3><?= $c['codigo_cartao'] ?></h3>

        <p class="numero-cartao">
            XXXX XXXX XXXX <?= substr($c['numero_cartao'], -4) ?>
        </p>

        <p><strong>Ciclo Atual:</strong> #<?= $c['id_ciclo'] ?></p>

        <p><strong>Saldo Inicial:</strong>  
            R$ <?= number_format($c['saldo_entregue'], 2, ',', '.') ?>
        </p>

        <p><strong>Gastos Declarados:</strong>  
            R$ <?= number_format($totalGastos, 2, ',', '.') ?>
        </p>

        <p><strong>Saldo Disponível no Ciclo:</strong>  
            R$ <?= number_format($saldoEsperado, 2, ',', '.') ?>
        </p>

        <div class="status status-<?= strtolower(str_replace(' ', '-', $c['status'])) ?>">
            <div class="status-bolinha"></div>
            <?= $c['status'] ?>
        </div>

        <p><strong>Atribuído em:</strong> <?= date('d/m/Y H:i', strtotime($c['data_atribuicao'])) ?></p>

        <div class="acoes-cartao">

            <!-- BOTÕES QUANDO EM USO -->
            <?php if ($c['status'] === 'EM USO'): ?>
               <a href="cartoes_registrar_gasto.php?cartao=<?= $c['codigo_cartao'] ?>"
                class="btn btn-acao">
                💵 Registrar Gasto
                </a>
            <?php endif; ?>

            <!-- HISTÓRICO -->
            <a href="cartoes_historico_pessoal.php?cartao=<?= $c['codigo_cartao'] ?>"
            class="btn btn-acao btn-cinza">
            📜 Histórico
            </a>

        </div>

    </div>

<?php endwhile; ?>

</div>

</div>

<?php
$conteudo = ob_get_clean();
include __DIR__ . '/../../includes/layout.php';
?>
