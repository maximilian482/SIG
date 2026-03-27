<?php
require_once __DIR__ . '/../../config/bootstrap.php';
session_start();
require_once ROOT_PATH . '/includes/funcoes.php';

$conn = conectar();

// ===============================
// 1. Dados do usuário logado
// ===============================
$idUsuario = intval(
    $_SESSION['id_funcionario']
    ?? $_SESSION['funcionario_id']
    ?? 0
);

$cpf          = $_SESSION['cpf'] ?? '';
$cargo        = strtolower($_SESSION['cargo'] ?? '');
$lojaUsuario  = intval($_SESSION['loja'] ?? 0);

if ($idUsuario <= 0) {
    header("Location: /login.php");
    exit;
}

// ===============================
// 2. Setores liberados
// ===============================
$setoresLiberados = usuarioTemSetores($conn, $cpf);
$setoresLiberadosIds = array_unique(array_map('intval', array_column($setoresLiberados, 'id')));

// ===============================
// 3. Regras de gerente/subgerente
// ===============================
$isGerencia = in_array($cargo, ['gerente', 'subgerente'], true);

// ===============================
// 4. Construção da query
// ===============================
$condicoes = [];
$parametros = [];
$tipos = "";

// 4.1 Tarefas atribuídas diretamente ao usuário
$condicoes[] = "(t.responsavel_tipo = 'funcionario' AND t.responsavel_id = ?)";
$parametros[] = $idUsuario;
$tipos .= "i";

// 4.2 Tarefas atribuídas aos setores do usuário
if (!empty($setoresLiberadosIds)) {
    $in = implode(",", $setoresLiberadosIds);
    $condicoes[] = "(t.responsavel_tipo = 'setor' AND t.responsavel_id IN ($in))";
}

// 4.3 Tarefas atribuídas à loja (somente gerente/subgerente)
if ($isGerencia && $lojaUsuario > 0) {
    $condicoes[] = "(t.responsavel_tipo = 'loja' AND t.responsavel_id = ?)";
    $parametros[] = $lojaUsuario;
    $tipos .= "i";
}

$whereResponsaveis = implode(" OR ", $condicoes);

// ===============================
// 5. Query final
// ===============================
$sql = "
    SELECT 
        t.*,
        p.titulo AS titulo_plano
    FROM tarefas_plano t
    JOIN planos_acao p ON p.id = t.id_plano
    WHERE 
        p.status = 'ativa'
        AND ($whereResponsaveis)
    ORDER BY t.data_limite ASC
";

$stmt = $conn->prepare($sql);
if ($tipos !== '') {
    $stmt->bind_param($tipos, ...$parametros);
}
$stmt->execute();
$tarefas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ===============================
// 6. Separar por status (3 abas)
// reaberta entra em pendentes
// ===============================
$lista = [
    'pendentes'  => [],
    'aguardando' => [],
    'concluidas' => []
];

foreach ($tarefas as $t) {
    $s = strtolower(trim($t['status']));

    if ($s === 'aguardando_avaliacao') {
        $lista['aguardando'][] = $t;
    } elseif (in_array($s, ['concluida', 'avaliada'], true)) {
        $lista['concluidas'][] = $t;
    } else {
        // pendente, em_andamento, reaberta, etc.
        $lista['pendentes'][] = $t;
    }
}

// ===============================
// 7. HTML (conteúdo)
// ===============================
ob_start();
?>
<link rel="stylesheet" href="/css/minhas_tarefas.css">

<div class="container-minhas-tarefas">

    <h1>Minhas Tarefas</h1>
    <p class="subtitle">
        Aqui você vê as tarefas atribuídas ao seu nome, setor ou loja, em planos de ação ativos.
    </p>

    <!-- Abas -->
    <div class="tabs" role="tablist">
        <button class="tab" aria-selected="true" data-filter="pendentes">
            Pendentes <span class="count">(<?= count($lista['pendentes']) ?>)</span>
        </button>
        <button class="tab" aria-selected="false" data-filter="aguardando">
            Aguardando Avaliação <span class="count">(<?= count($lista['aguardando']) ?>)</span>
        </button>
        <button class="tab" aria-selected="false" data-filter="concluidas">
            Concluídas <span class="count">(<?= count($lista['concluidas']) ?>)</span>
        </button>
    </div>

    <!-- GRUPOS POR STATUS -->
    <?php foreach ($lista as $status => $tarefasStatus): ?>

        <?php
        // AGRUPAR POR PLANO
        $agrupado = [];

        foreach ($tarefasStatus as $t) {
            $idPlano = $t['id_plano'];

            if (!isset($agrupado[$idPlano])) {
                $agrupado[$idPlano] = [
                    'titulo_plano' => $t['titulo_plano'],
                    'tarefas'      => []
                ];
            }

            $agrupado[$idPlano]['tarefas'][] = $t;
        }
        ?>

        <div class="grupo<?= $status !== 'pendentes' ? ' hidden' : '' ?>" data-grupo="<?= $status ?>">

            <?php if (empty($agrupado)): ?>
                <p class="empty">Nenhuma tarefa nesta categoria.</p>
            <?php else: ?>

                <?php foreach ($agrupado as $idPlano => $plano): ?>

                    <div class="card-plano">

                        <h2><?= htmlspecialchars($plano['titulo_plano']) ?></h2>

                        <div class="tarefas-grid">

                            <?php foreach ($plano['tarefas'] as $t): ?>
                                <?php
                                $prazo = calcularPrazoClasse($t['data_limite'] ?? null);
                                $statusBruto = strtolower(trim($t['status']));
                                $statusFormatado = formatarStatusTarefa($statusBruto);
                                $isReaberta = ($statusBruto === 'reaberta');
                                ?>
                                <div class="tarefa-card">

                                    <div>
                                        <strong><?= htmlspecialchars($t['titulo']) ?></strong>

                                        <div class="linha-status-prazo">
                                            <span class="prazo-pill <?= $prazo['class'] ?>">
                                                <?= $prazo['label'] ?>
                                            </span>

                                            <?php if ($isReaberta): ?>
                                                <span class="badge-reaberta">
                                                    Reaberta pelo avaliador
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="status-legenda">
                                            Status: <?= htmlspecialchars($statusFormatado) ?>
                                        </div>

                                        <?php if (!empty($t['descricao'])): ?>
                                            <div class="descricao">
                                                <small><?= nl2br(htmlspecialchars($t['descricao'])) ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="acoes-tarefa">

                                        <!-- BOTÃO CORRETO (SEM <a>) -->
                                        <button 
                                            class="btn-sec btn-ver-tarefa" 
                                            data-id="<?= $t['id'] ?>" 
                                            style="width:100%;">
                                            Ver detalhes
                                        </button>

                                        <?php if ($status === 'pendentes'): ?>
                                            <button
                                                class="btn btn-marcar-feita"
                                                style="width:100%;"
                                                data-id="<?= (int)$t['id'] ?>"
                                                data-titulo="<?= htmlspecialchars($t['titulo'], ENT_QUOTES) ?>"
                                            >
                                                Marcar como feita
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    <?php endforeach; ?>

</div>

<?php
$conteudo = ob_get_clean();

// ===============================
// 8. MODAL FECHAR TAREFA
// ===============================
ob_start();
?>
<div id="modalFecharTarefa" class="plano-modal hidden">
    <div class="plano-modal-content">
        <button class="plano-modal-close">×</button>

        <h2 id="modal-titulo-tarefa">Fechar tarefa</h2>
        <p class="modal-texto-ajuda">
            Descreva brevemente o que foi feito para concluir esta tarefa.
        </p>

        <form id="formFecharTarefa">
            <input type="hidden" name="id_tarefa" id="modal-id-tarefa">

            <label for="modal-resposta" class="modal-label">
                O que foi feito?
            </label>

            <textarea
                id="modal-resposta"
                name="resposta"
                rows="4"
                class="modal-textarea"
                placeholder="Descreva a ação realizada..."
            ></textarea>

            <div class="contador-caracteres">
                <span id="contador-resposta">0</span> caracteres
            </div>

            <div class="modal-botoes">
                <button type="button" class="btn-sec plano-modal-close">
                    Cancelar
                </button>
                <button type="submit" class="btn">
                    Enviar e concluir tarefa
                </button>
            </div>
        </form>
    </div>
</div>


<!-- =============================== -->
<!-- 9. MODAL DETALHES DA TAREFA     -->
<!-- =============================== -->
<div id="modalDetalhesTarefa" class="plano-modal hidden">
    <div class="plano-modal-content modal-avaliacao-content">
        <button class="plano-modal-close">×</button>
        <div id="conteudoDetalhesTarefa"></div>
    </div>
</div>

<?php
$modais = ob_get_clean();

// ===============================
// 10. Scripts específicos
// ===============================
$scripts = '
    <script src="/js/planos_acao_detalhes.js"></script>
    <script src="/js/minhas_tarefas.js"></script>
';

// ===============================
// 11. Carregar layout
// ===============================
include ROOT_PATH . '/includes/layout.php';
