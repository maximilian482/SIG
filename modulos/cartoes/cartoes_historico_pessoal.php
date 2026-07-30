<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';
$conn = conectar();

$cpf = $_SESSION['cpf'] ?? '';
$cartao = $_GET['cartao'] ?? '';

if (!$cpf) {
    header("Location: ../../login.php");
    exit;
}

// Buscar ciclos do funcionário para este cartão
$stmt = $conn->prepare("
    SELECT cc.*, 
           a.saldo_entregue, 
           a.data_atribuicao,
           a.cpf_funcionario
    FROM cartoes_ciclos cc
    JOIN cartoes_atribuicoes a ON a.id_ciclo = cc.id_ciclo
    WHERE cc.codigo_cartao = ?
      AND a.cpf_funcionario = ?
    ORDER BY cc.id_ciclo DESC
");
$stmt->bind_param("ss", $cartao, $cpf);
$stmt->execute();
$ciclos = $stmt->get_result();

ob_start();
?>

<link rel="stylesheet" href="/css/cartoes_historico.css">

<div class="cartoes-modulo-historico">

    <h1>📜 Meu Histórico de Uso</h1>
    <p class="subtitulo">Histórico completo do cartão <strong><?= $cartao ?></strong>.</p>

<?php while ($c = $ciclos->fetch_assoc()): ?>

    <?php
        // Buscar gastos do ciclo
        $stmtG = $conn->prepare("
            SELECT *
            FROM cartoes_gastos
            WHERE id_ciclo = ?
            ORDER BY data_compra ASC
        ");
        $stmtG->bind_param("i", $c['id_ciclo']);
        $stmtG->execute();
        $gastos = $stmtG->get_result();

        // Calcular total de gastos
        $totalGastos = 0;
        foreach ($gastos as $g) {
            $totalGastos += floatval($g['valor_parcela']);
        }

        // Saldo esperado
        $saldoEsperado = $c['saldo_inicial'] - $totalGastos;
    ?>

    <div class="ciclo-bloco">

        <h2>🔵 Ciclo #<?= $c['id_ciclo'] ?></h2>

        <p><strong>Entrega:</strong> <?= date('d/m/Y H:i', strtotime($c['data_entrega'])) ?></p>
        <p><strong>Saldo inicial:</strong> R$ <?= number_format($c['saldo_inicial'], 2, ',', '.') ?></p>

        <p><strong>Total de gastos declarados:</strong>  
            R$ <?= number_format($totalGastos, 2, ',', '.') ?>
        </p>

        <p><strong>Saldo esperado:</strong>  
            R$ <?= number_format($saldoEsperado, 2, ',', '.') ?>
        </p>

        <?php if ($c['status'] === 'DEVOLVIDO'): ?>
            <p><strong>Saldo conferido no banco:</strong>  
                R$ <?= number_format($c['saldo_banco'], 2, ',', '.') ?>
            </p>

            <p><strong>Divergência:</strong>  
                <?= $c['divergencia'] == 0 ? "✔ Sem divergência" : "⚠ R$ " . number_format($c['divergencia'], 2, ',', '.') ?>
            </p>

            <p><strong>Devolvido em:</strong> <?= date('d/m/Y H:i', strtotime($c['data_devolucao'])) ?></p>
        <?php endif; ?>

        <hr>

        <h3>🧾 Gastos do Ciclo</h3>

        <?php if ($gastos->num_rows == 0): ?>
            <p class="text-muted">Nenhum gasto registrado neste ciclo.</p>
        <?php else: ?>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Parcela</th>
                        <th>Valor</th>
                        <th>Comprovante</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($gastos as $g): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($g['data_compra'])) ?></td>
                            <td><?= $g['descricao'] ?></td>
                            <td><?= $g['parcelas'] ?>/<?= $g['total_parcelas'] ?></td>
                            <td>R$ <?= number_format($g['valor_parcela'], 2, ',', '.') ?></td>
                            <td>
                                <a href="/uploads/comprovantes/<?= $g['comprovante'] ?>" target="_blank">
                                    📄 Ver
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>

    </div>

<?php endwhile; ?>

    <a href="cartoes_funcionario.php" class="btn-voltar">⬅ Voltar</a>

</div>

<?php
$conteudo = ob_get_clean();
include __DIR__ . '/../../includes/layout.php';
?>
