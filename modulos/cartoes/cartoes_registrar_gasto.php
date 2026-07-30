<?php
session_start();

require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../dados/conexao.php';

$conn = conectar();

// Dados do funcionário logado
$cpf  = $_SESSION['cpf'] ?? '';
$nome = $_SESSION['usuario'] ?? '';

if (!$cpf) {
    header("Location: ../../login.php");
    exit;
}

// Verifica se o cartão foi enviado corretamente
$cartao = $_GET['cartao'] ?? '';

if (!$cartao) {
    $_SESSION['flash'] = [
        'mensagem' => 'Cartão não informado.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_funcionario.php");
    exit;
}

// Busca dados do cartão atribuído ao funcionário + ciclo ativo
$stmt = $conn->prepare("
    SELECT c.*, a.id AS id_atribuicao, a.id_ciclo
    FROM cartoes_atribuicoes a
    JOIN cartoes c ON c.codigo_cartao = a.codigo_cartao
    WHERE a.cpf_funcionario = ?
      AND a.codigo_cartao = ?
      AND a.ativo = 1
    LIMIT 1
");
$stmt->bind_param("ss", $cpf, $cartao);
$stmt->execute();
$dados = $stmt->get_result()->fetch_assoc();

if (!$dados) {
    $_SESSION['flash'] = [
        'mensagem' => 'Este cartão não está atribuído a você.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_funcionario.php");
    exit;
}

$id_atribuicao = $dados['id_atribuicao'];
$id_ciclo      = $dados['id_ciclo'];

// Buscar setor do funcionário
$stmtSetor = $conn->prepare("
    SELECT id_setor
    FROM funcionarios
    WHERE cpf = ?
    LIMIT 1
");
$stmtSetor->bind_param("s", $cpf);
$stmtSetor->execute();
$id_setor = $stmtSetor->get_result()->fetch_assoc()['id_setor'] ?? null;

ob_start();
?>

<link rel="stylesheet" href="/css/cartoes.css">

<div class="cartoes-modulo-funcionario">

<h1 class="mb-3">💵 Registrar Gasto</h1>
<p>Você está registrando um gasto no cartão <strong><?= $cartao ?></strong> (Ciclo #<?= $id_ciclo ?>).</p>

<div class="cartoes-card">

    <form method="POST" action="cartoes_registrar_gasto_salvar.php" enctype="multipart/form-data">

        <input type="hidden" name="cartao" value="<?= $cartao ?>">
        <input type="hidden" name="id_atribuicao" value="<?= $id_atribuicao ?>">
        <input type="hidden" name="id_setor" value="<?= $id_setor ?>">
        <input type="hidden" name="id_ciclo" value="<?= $id_ciclo ?>"> <!-- NOVO -->

        <label class="form-label">Data da Compra</label>
        <input type="date" name="data_compra" class="form-control" required>

        <label class="form-label mt-3">Descrição</label>
        <input type="text" name="descricao" class="form-control" required>

        <label class="form-label mt-3">Valor Total da Compra</label>
        <input type="number" step="0.01" name="valor" class="form-control" required>

        <label class="form-label mt-3">Quantidade de Parcelas</label>
        <input type="number" name="parcelas" class="form-control" min="1" value="1" required>

        <label class="form-label mt-3">Comprovante (foto ou PDF)</label>
        <input type="file" name="comprovante" class="form-control" required>

        <button class="btn btn-novo mt-4">Salvar Registro</button>

    </form>

        <a href="cartoes_funcionario.php" class="btn btn-secondary w-100 mt-3">
            ⬅ Voltar
        </a>


</div>

<?php
// Buscar gastos do funcionário SOMENTE DO CICLO ATUAL
$stmtGastos = $conn->prepare("
    SELECT *
    FROM cartoes_gastos
    WHERE cpf_funcionario = ?
      AND id_ciclo = ?
    ORDER BY data_compra DESC
");
$stmtGastos->bind_param("si", $cpf, $id_ciclo);
$stmtGastos->execute();
$gastos = $stmtGastos->get_result();

if ($gastos->num_rows > 0):
?>

<div class="gastos-lista mt-5">

    <h2>📄 Seus Gastos no Ciclo Atual (#<?= $id_ciclo ?>)</h2>
    <p class="subtitulo">Aqui estão os gastos registrados neste ciclo.</p>

    <table class="tabela-padrao">
        <thead>
            <tr>
                <th>Data</th>
                <th>Cartão</th>
                <th>Descrição</th>
                <th>Ações</th>
                <th>Parcela</th>
                <th>Total Parcelas</th>
                <th>Valor da Parcela</th>
                <th>Comprovante</th>
            </tr>
        </thead>

        <tbody>
            <?php while ($g = $gastos->fetch_assoc()): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($g['data_compra'])) ?></td>
                    <td><?= $g['codigo_cartao'] ?></td>
                    <td><?= $g['descricao'] ?></td>
                    <td>
    <a href="cartoes_editar_gasto.php?id=<?= $g['id'] ?>" class="btn btn-sm btn-outline-secondary">
        ✏ Editar
    </a>
</td>

                    <td><?= $g['parcelas'] ?: '—' ?></td>
                    <td><?= $g['total_parcelas'] ?: '—' ?></td>
                    <td>R$ <?= number_format($g['valor_parcela'], 2, ',', '.') ?></td>
                    <td>
                        <a href="/uploads/comprovantes/<?= $g['comprovante'] ?>" target="_blank">
                            📄 Ver
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</div>

<?php endif; ?>

</div>

<?php
$conteudo = ob_get_clean();
include __DIR__ . '/../../includes/layout.php';
?>
