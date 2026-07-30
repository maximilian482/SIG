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

// Busca cartões cadastrados
$cartoes = $conn->query("
    SELECT codigo_cartao, banco, status, ultimo_utilizador
    FROM cartoes 
    ORDER BY codigo_cartao ASC
");

// Busca funcionários
$funcionarios = $conn->query("
    SELECT nome, cpf
    FROM funcionarios
    ORDER BY nome ASC
");

ob_start();
?>
<link rel="stylesheet" href="/css/cartoes.css">

<div class="container py-4" style="max-width: 900px;">

    <h1 class="mb-3">📇 Registro de Ocorrências</h1>
    <p class="text-muted">Registre eventos anormais relacionados aos cartões corporativos.</p>

    <a href="/modulos/cartoes/cartoes_mestre.php" class="btn btn-secondary mb-3">⬅ Voltar</a>

    <!-- CARD DE FORMULÁRIO -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <h3 class="mb-3">Nova Ocorrência</h3>

            <form method="POST" action="/modulos/cartoes/cartoes_ocorrencias_salvar.php" class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Data da Ocorrência:</label>
                    <input type="date" name="data_ocorrencia" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Tipo de Ocorrência:</label>
                    <select name="tipo" class="form-select" required>
                        <option value="">Selecione...</option>
                        <option value="EXTRAVIO">Extravio</option>
                        <option value="PERDA">Perda</option>
                        <option value="ROUBO">Roubo</option>
                        <option value="DANIFICADO">Cartão Danificado</option>
                        <option value="FRAUDE">Suspeita de Fraude</option>
                        <option value="CONTESTACAO">Contestação</option>
                        <option value="OBSERVACAO">Observação Interna</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Funcionário Envolvido:</label>
                    <select name="utilizador" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php while ($f = $funcionarios->fetch_assoc()): ?>
                            <option value="<?= $f['nome'] ?>">
                                <?= $f['nome'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Cartão:</label>
                    <select name="codigo_cartao" class="form-select" required>
                        <?php while ($c = $cartoes->fetch_assoc()): ?>
                            <option value="<?= $c['codigo_cartao'] ?>">
                                <?= $c['codigo_cartao'] ?> — <?= $c['banco'] ?> (<?= $c['status'] ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Saldo Atual:</label>
                    <input type="number" step="0.01" name="saldo_atual" class="form-control" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Descrição da Ocorrência:</label>
                    <textarea name="observacao" rows="3" class="form-control"></textarea>
                </div>

                <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                    <button class="btn btn-success">💾 Registrar Ocorrência</button>
                    <a href="/modulos/cartoes/cartoes_mestre.php" class="btn btn-secondary">Cancelar</a>
                </div>

            </form>

        </div>
    </div>

    <!-- LISTAGEM -->
    <div class="card shadow-sm">
        <div class="card-body">

            <h3 class="mb-3">Últimas Ocorrências</h3>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Funcionário</th>
                            <th>Cartão</th>
                            <th>Saldo</th>
                            <th>Descrição</th>
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php
                    $oc = $conn->query("
                        SELECT id, data_ocorrencia, tipo, utilizador, codigo_cartao, saldo_atual, observacao
                        FROM cartoes_ocorrencias
                        ORDER BY id DESC
                        LIMIT 20
                    ");

                    while ($o = $oc->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($o['data_ocorrencia'])) ?></td>
                        <td><?= $o['tipo'] ?></td>
                        <td><?= $o['utilizador'] ?></td>
                        <td><?= $o['codigo_cartao'] ?></td>
                        <td>R$ <?= number_format($o['saldo_atual'], 2, ',', '.') ?></td>
                        <td><?= $o['observacao'] ?></td>

                        <td class="text-end">
                            <form method="POST" action="/modulos/cartoes/cartoes_ocorrencias_excluir.php"
                                  onsubmit="return confirm('Excluir esta ocorrência?');">
                                <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                <button class="btn btn-sm btn-danger">🗑</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>

<?php
$conteudo = ob_get_clean();
include __DIR__ . '/../../includes/layout.php';
?>
