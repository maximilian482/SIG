<?php
session_start();

require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../dados/conexao.php';

$conn = conectar();

// CPF sempre limpo e padronizado
$cpfLogado = trim(preg_replace('/\D/', '', $_SESSION['cpf'] ?? ''));

// Verifica acesso pelo EDITAR ACESSOS
if (!temAcesso($conn, $cpfLogado, 'cartoes')) {
    $_SESSION['flash'] = [
        'mensagem' => 'Você não possui acesso ao módulo de Cartões Corporativos.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_mestre.php");
    exit;
}

// Detecta se é edição
$editar_id = $_GET['editar'] ?? null;
$editar_dados = null;

if ($editar_id) {
    $stmt = $conn->prepare("
        SELECT *
        FROM cartoes_atribuicoes
        WHERE id = ?
    ");
    $stmt->bind_param("i", $editar_id);
    $stmt->execute();
    $editar_dados = $stmt->get_result()->fetch_assoc();
}

// Busca cartões disponíveis OU o cartão da atribuição sendo editada
if ($editar_dados) {
    $cartoes = $conn->query("
        SELECT codigo_cartao, banco, COALESCE(saldo_atual, 0) AS saldo_atual
        FROM cartoes
        WHERE status = 'DISPONÍVEL'
           OR codigo_cartao = '{$editar_dados['codigo_cartao']}'
        ORDER BY codigo_cartao ASC
    ");
} else {
    $cartoes = $conn->query("
        SELECT codigo_cartao, banco, COALESCE(saldo_atual, 0) AS saldo_atual
        FROM cartoes
        WHERE status = 'DISPONÍVEL'
        ORDER BY codigo_cartao ASC
    ");
}

// Busca funcionários cadastrados
$funcionarios = $conn->query("
    SELECT nome, cpf, id_setor 
    FROM funcionarios 
    ORDER BY nome ASC
");

// Busca setores
$setores = [];
$set = $conn->query("SELECT id, nome FROM setores");
while ($s = $set->fetch_assoc()) {
    $setores[$s['id']] = $s['nome'];
}

// Busca atribuições já feitas
$atribuicoes = $conn->query("
    SELECT a.*, f.nome, f.id_setor
    FROM cartoes_atribuicoes a
    JOIN funcionarios f ON f.cpf = a.cpf_funcionario
    WHERE a.ativo = 1
    ORDER BY a.data_atribuicao DESC
");

ob_start();
?>

<div class="container py-4" style="max-width: 900px;">

    <h1 class="mb-3">🔧 Atribuição de Cartões</h1>

    <a href="cartoes_mestre.php" class="btn btn-secondary mb-3">
        ⬅ Voltar
    </a>

    <?php if ($editar_dados): ?>
        <p class="text-warning"><strong>Editando atribuição #<?= $editar_id ?></strong></p>
    <?php else: ?>
        <p class="text-muted">Selecione o cartão, funcionário e salve a atribuição.</p>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <form method="POST" action="cartoes_atribuir_salvar.php" class="row g-3">

                <input type="hidden" name="editar_id" value="<?= $editar_id ?>">

                <!-- NOVO: campo para manter o ciclo quando estiver editando -->
                <input type="hidden" name="id_ciclo" value="<?= $editar_dados['id_ciclo'] ?? '' ?>">

                <div class="col-md-6">
                    <label class="form-label"><strong>Cartão:</strong></label>
                    <select name="codigo_cartao" id="codigo_cartao" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php while ($c = $cartoes->fetch_assoc()): ?>
                            <option value="<?= $c['codigo_cartao'] ?>"
                                <?= ($editar_dados && $editar_dados['codigo_cartao'] == $c['codigo_cartao']) ? 'selected' : '' ?>
                                data-saldo="<?= number_format($c['saldo_atual'], 2, '.', '') ?>">
                                <?= $c['codigo_cartao'] ?> — <?= $c['banco'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><strong>Funcionário:</strong></label>
                    <select name="cpf_funcionario" id="cpf_funcionario" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php while ($f = $funcionarios->fetch_assoc()): ?>
                            <option value="<?= $f['cpf'] ?>"
                                <?= ($editar_dados && $editar_dados['cpf_funcionario'] == $f['cpf']) ? 'selected' : '' ?>
                                data-setor="<?= $setores[$f['id_setor']] ?>">
                                <?= $f['nome'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label"><strong>Saldo Atual (editável)</strong></label>
                    <input type="number" step="0.01" name="saldo_atual" id="saldo_atual"
                           value="<?= $editar_dados ? number_format($editar_dados['saldo_entregue'], 2, '.', '') : '' ?>"
                           class="form-control" required>
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <?php if ($editar_dados): ?>
                        <button class="btn btn-warning w-100">💾 Salvar Edição</button>
                    <?php else: ?>
                        <button class="btn btn-success w-100">💾 Salvar Atribuição</button>
                    <?php endif; ?>
                </div>

            </form>

        </div>
    </div>

    <h3 class="mb-3">📋 Atribuições Registradas</h3>

    <div class="table-responsive mb-4">
        <table class="table table-striped table-hover">
            <thead class="table-light">
                <tr>
                    <th>Cartão</th>
                    <th>Ciclo</th>
                    <th>Funcionário</th>
                    <th>Setor</th>
                    <th>Saldo</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="atribuicoesTabela">
                <?php while ($a = $atribuicoes->fetch_assoc()): 
                    $nome = $a['nome'];
                    $p = explode(" ", trim($nome));
                    $nomeReduzido = $p[0] . " " . end($p);
                    $setor = $setores[$a['id_setor']];
                ?>
                <tr>
                    <td><?= $a['codigo_cartao'] ?></td>
                    <td>##<?= $a['id_ciclo'] ?></td>
                    <td><?= $nomeReduzido ?></td>
                    <td><?= $setor ?></td>
                    <td>R$ <?= number_format($a['saldo_entregue'], 2, ',', '.') ?></td>
                    <td><?= date('d/m/Y', strtotime($a['data_atribuicao'])) ?></td>
                    <td>
                        <?php if (!$a['assinatura_funcionario']): ?>
                            <a href="cartoes_assinar.php?id=<?= $a['id'] ?>" class="btn btn-primary btn-sm">✍ Assinar</a>
                        <?php else: ?>
                            <a href="cartoes_ver_assinaturas.php?id=<?= $a['id'] ?>" class="btn btn-secondary btn-sm">👁 Ver</a>
                        <?php endif; ?>

                        <?php if (!$a['assinatura_funcionario']): ?>
                            <a href="cartoes_atribuir.php?editar=<?= $a['id'] ?>" class="btn btn-warning btn-sm">✏ Editar</a>
                        <?php endif; ?>

                        <?php if (!$a['assinatura_funcionario']): ?>
                            <a href="cartoes_excluir_atribuicao.php?id=<?= $a['id'] ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Tem certeza que deseja excluir esta atribuição?');">
                               ❌ 
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
function atualizarSaldo() {
    let select = document.getElementById("codigo_cartao");
    let saldo = parseFloat(select.options[select.selectedIndex].dataset.saldo || 0);
    document.getElementById("saldo_atual").value = saldo.toFixed(2);
}

document.getElementById("codigo_cartao").addEventListener("change", atualizarSaldo);
</script>

<?php
$conteudo = ob_get_clean();
include __DIR__ . '/../../includes/layout.php';
?>
