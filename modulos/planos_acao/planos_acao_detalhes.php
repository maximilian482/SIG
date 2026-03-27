<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';

$conn = conectar();

$id_plano = intval($_GET['id'] ?? 0);
if ($id_plano <= 0) {
    http_response_code(400);
    die("Plano inválido.");
}

// Buscar dados do plano
$sqlPlano = "SELECT * FROM planos_acao WHERE id = ? LIMIT 1";
$stmtPlano = $conn->prepare($sqlPlano);
$stmtPlano->bind_param("i", $id_plano);
$stmtPlano->execute();
$plano = $stmtPlano->get_result()->fetch_assoc();
$stmtPlano->close();

if (!$plano) {
    http_response_code(404);
    die("Plano não encontrado.");
}

// Buscar tarefas
$sqlTarefas = "SELECT * FROM tarefas_plano WHERE id_plano = ? ORDER BY COALESCE(criado_em, atualizado_em, id)";
$stmtT = $conn->prepare($sqlTarefas);
$stmtT->bind_param("i", $id_plano);
$stmtT->execute();
$tarefas = $stmtT->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtT->close();

// Contadores
$countPendentes = 0;
$countAguardando = 0;
$countConcluidas = 0;

foreach ($tarefas as $t) {
    $s = $t['status'];
    if ($s === 'aguardando_avaliacao') $countAguardando++;
    elseif ($s === 'concluida' || $s === 'avaliada') $countConcluidas++;
    else $countPendentes++;
}

ob_start();
?>

<div class="container plano-detalhes">

    <div class="header-row">
        <div>
            <h1>Plano de Ação: <?= htmlspecialchars($plano['titulo']) ?></h1>
            <div class="small">
                Período:
                <?= $plano['data_inicio'] ? date('d/m/Y', strtotime($plano['data_inicio'])) : '(sem início)' ?>
                <?php if ($plano['data_fim']): ?>
                    a <?= date('d/m/Y', strtotime($plano['data_fim'])) ?>
                <?php endif; ?>
                • Criado por: <?= nomeFuncionarioCached($conn, $plano['criado_por']) ?>
            </div>
        </div>

        <div class="top-actions">
            <a href="tarefa_plano_criar.php?id_plano=<?= $id_plano ?>" class="btn">+ Criar Nova Tarefa</a>
            <a href="plano_adicionar_tarefa.php?id_plano=<?= $id_plano ?>" class="btn secondary">+ Adicionar por Modelo</a>
        </div>
    </div>

    <div class="tabs">
        <button class="tab" data-filter="pendentes">Pendentes (<?= $countPendentes ?>)</button>
        <button class="tab" data-filter="aguardando">Aguardando Avaliação (<?= $countAguardando ?>)</button>
        <button class="tab" data-filter="concluidas">Concluídas (<?= $countConcluidas ?>)</button>
    </div>

    <?php if (!$tarefas): ?>
        <p>Nenhuma tarefa adicionada ainda.</p>
    <?php else: ?>

    <table class="table">
        <thead>
            <tr>
                <th>Título</th>
                <th>Responsável</th>
                <th>Prazo</th>
                <th>Ações</th>
            </tr>
        </thead>

        <tbody id="tarefas-body">
        <?php foreach ($tarefas as $t):

            $tarefaId = $t['id'];
            $status   = $t['status'];

            $aba = match ($status) {
                'aguardando_avaliacao' => 'aguardando',
                'concluida' => 'concluidas',
                'reaberta' => 'pendentes',
                default => 'pendentes'
            };

            $prazo = calcularPrazoClasse($t['data_limite']);

        ?>
            <tr data-aba="<?= $aba ?>" id="tarefa-row-<?= $tarefaId ?>">

                <td><?= htmlspecialchars($t['titulo']) ?></td>

                <td><?= resolverResponsavel($conn, $t) ?></td>

                <td>
                    <span class="prazo-pill <?= $prazo['class'] ?>">
                        <?= $prazo['label'] ?>
                    </span>
                </td>

                <td>
                    <div class="actions">

    <?php if ($aba === 'pendentes'): ?>

        <a href="tarefa_plano_editar.php?id=<?= $tarefaId ?>" class="action-icon" title="Editar">✏️</a>

        <a href="tarefa_plano_excluir.php?id=<?= $tarefaId ?>" 
           class="action-icon danger" title="Excluir"
           onclick="return confirm('Excluir esta tarefa?');">🗑️</a>

        <a href="planos_acao_tarefa_clonar.php?id=<?= $tarefaId ?>" 
           class="action-icon clone" title="Clonar">📄</a>

    <?php elseif ($aba === 'aguardando'): ?>

        <button class="action-icon info" onclick="abrirModalAvaliacao(<?= $tarefaId ?>)">📝 Avaliar</button>

    <?php elseif ($aba === 'concluidas'): ?>

        <!-- REABRIR VIA MODAL -->
        <button class="action-icon warning"
                title="Reabrir"
                onclick="abrirModalAvaliacao(<?= $tarefaId ?>)">
            🔄
        </button>

    <?php endif; ?>

    <button class="action-icon info" onclick="abrirDetalhesTarefa(<?= $tarefaId ?>)">🔍</button>

    <?php if ($status === 'reaberta'): ?>
        <button class="action-icon warning" onclick="abrirDetalhesTarefa(<?= $tarefaId ?>)">⚠️</button>
    <?php endif; ?>

                    </div>
                </td>

            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php endif; ?>

    <a href="planos_acao_listar.php" class="btn ghost">← Voltar</a>
</div>

<!-- MODAIS -->
<div id="modalAvaliacao" class="plano-modal hidden">
    <div class="plano-modal-content modal-avaliacao-content">
        <button class="plano-modal-close">×</button>
        <div id="modalConteudo"></div>
    </div>
</div>

<div id="modalReaberta" class="plano-modal hidden">
    <div class="plano-modal-content modal-avaliacao-content">
        <button class="plano-modal-close">×</button>
        <div id="conteudoReaberta"></div>
    </div>
</div>

<div id="modalDetalhesTarefa" class="plano-modal hidden">
    <div class="plano-modal-content modal-avaliacao-content">
        <button class="plano-modal-close">×</button>
        <div id="conteudoDetalhesTarefa"></div>
    </div>
</div>

<script>
    const ID_PLANO = <?= $id_plano ?>;
</script>

<?php
$conteudo = ob_get_clean();
$scripts = '<script src="/js/planos_acao_detalhes.js"></script>';
$cssExtra = "/css/planos_acao_detalhes.css";


if (!empty($_SESSION['flash']) && $_SESSION['flash']['tipo'] === 'error') {
    unset($_SESSION['flash']);
}

include ROOT_PATH . "/includes/layout.php";
