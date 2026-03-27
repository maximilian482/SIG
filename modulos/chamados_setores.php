<?php
session_start();
session_write_close();

require_once '../dados/conexao.php';
require_once '../includes/funcoes.php';
date_default_timezone_set('America/Sao_Paulo');

$conn = conectar();

// =====================================================
// 0) Verificação básica de sessão
// =====================================================
if (!isset($_SESSION['cargo'])) {
    header("Location: /login.php");
    exit;
}

$cpf           = preg_replace('/\D+/', '', (string)($_SESSION['cpf'] ?? ''));
$cargo         = strtolower(trim($_SESSION['cargo'] ?? ''));
$idFuncionario = intval($_SESSION['funcionario_id'] ?? ($_SESSION['id_funcionario'] ?? 0));

// =====================================================
// 1) Gerente/Subgerente → chamados da loja
// =====================================================
if (in_array($cargo, ['gerente', 'subgerente'], true)) {
    header("Location: chamados_loja.php");
    exit;
}

// =====================================================
// 2) Buscar setores liberados (modelo novo por ID)
// =====================================================
$setoresSetor = usuarioTemSetores($conn, $cpf); 
// Ex: [['id'=>3,'nome'=>'Financeiro'], ['id'=>5,'nome'=>'TI']]

// garantir formato consistente
if (!is_array($setoresSetor)) $setoresSetor = [];

// extrair ids e nomes para uso posterior
$setoresIds   = array_map('intval', array_column($setoresSetor, 'id'));
$setoresNomes = array_map('trim', array_column($setoresSetor, 'nome'));

// detectar se usuário é super/ceo
$isSuperOuCeo = in_array($cargo, ['super', 'ceo'], true);

// =====================================================
// 3) Permissão manual de pendências (ID especial = 0)
// Só adicionar pendências para usuários que realmente têm essa permissão,
// não para super/ceo (evita que super/ceo vejam tudo por causa de pendências).
// =====================================================
if (!$isSuperOuCeo && temAcesso($conn, $cpf, 'acesso_pendencias')) {
    $setoresSetor[] = [
        'id'   => 0,
        'nome' => 'Pendências'
    ];
    $setoresIds[] = 0;
    $setoresNomes[] = 'Pendências';
}

// =====================================================
// 4) Se não tiver nenhum setor → bloqueia
// =====================================================
if (empty($setoresSetor)) {
    echo "<h2>❌ Você não tem acesso a nenhum setor de pendências.</h2>";
    exit;
}

// =====================================================
// 5) Se houver mais de um setor, o usuário deve escolher
// =====================================================
if (count($setoresSetor) > 1 && !isset($_GET['setor'])) {

    require_once __DIR__ . '/../config/bootstrap.php';
    include ROOT_PATH . '/includes/head.php';
    include ROOT_PATH . '/includes/menu.php';
    include ROOT_PATH . '/perfil/menu_perfil.php';

    echo "<h2>📌 Escolha o setor que deseja visualizar:</h2>";
    echo "<ul style='font-size:18px;'>";

    foreach ($setoresSetor as $s) {
        // se for pendências e usuário for super/ceo, mostrar apenas como informação (não dar acesso total)
        if ($isSuperOuCeo && isset($s['id']) && intval($s['id']) === 0) {
            echo "<li style='color:#777;'>🔒 " . htmlspecialchars($s['nome']) . " (somente leitura)</li>";
            continue;
        }
        echo "<li><a href='?setor=" . intval($s['id']) . "'>➡️ " . htmlspecialchars($s['nome']) . "</a></li>";
    }

    echo "</ul>";
    exit;
}

// =====================================================
// 6) Definir setor final (ID real) - agora que $setoresSetor existe
// =====================================================
$primeiroSetorId = null;
if (!empty($setoresSetor)) {
    $firstKey = array_key_first($setoresSetor);
    $primeiroSetorId = intval($setoresSetor[$firstKey]['id'] ?? 0);
}
$setorId = intval($_GET['setor'] ?? $primeiroSetorId);

// DEBUG TEMPORÁRIO - remover após uso
error_log("DEBUG sessao: funcionario_id=" . $idFuncionario .
          " id_setor=" . intval($_SESSION['id_setor'] ?? -1) .
          " cargo=" . ($cargo ?? '') .
          " cpf=" . $cpf);

error_log("DEBUG usuarioTemSetores retorno: " . json_encode($setoresSetor));
error_log("DEBUG setorId_get=" . intval($_GET['setor'] ?? -999) . " setorId_final=" . intval($setorId ?? -999));

// =====================================================
// 6.1) Obter ID do setor Diretoria (por segurança, por ID)
// =====================================================
$diretoriaId = null;
$stmtDir = $conn->prepare("SELECT id FROM setores WHERE LOWER(nome) LIKE '%diretoria%' LIMIT 1");
if ($stmtDir) {
    $stmtDir->execute();
    $rowDir = $stmtDir->get_result()->fetch_assoc();
    if ($rowDir) $diretoriaId = intval($rowDir['id']);
    $stmtDir->close();
}

// =====================================================
// 6.2) Buscar nome do setor selecionado (se estiver na lista do usuário)
// Se não estiver na lista e o usuário for super/ceo, buscar direto na tabela setores
// =====================================================
$setorNome = null;
foreach ($setoresSetor as $s) {
    if ((int)$s['id'] === $setorId) {
        $setorNome = $s['nome'];
        break;
    }
}

$setorForcadoParaSuper = false;
if ($setorNome === null && $isSuperOuCeo) {
    $stmtS = $conn->prepare("SELECT nome FROM setores WHERE id = ? LIMIT 1");
    if ($stmtS) {
        $stmtS->bind_param("i", $setorId);
        $stmtS->execute();
        $rowS = $stmtS->get_result()->fetch_assoc();
        if ($rowS) {
            $setorNome = $rowS['nome'];
            $setorForcadoParaSuper = true;
        }
        $stmtS->close();
    }
}

// Se ainda não encontrou, bloquear (usuário tentou acessar setor sem permissão)
if ($setorNome === null) {
    echo "<h2>❌ Setor inválido ou sem permissão.</h2>";
    exit;
}

// detectar se o setor selecionado é Diretoria (por ID quando possível)
$isSetorDiretoria = ($diretoriaId !== null && $setorId === $diretoriaId);

// Mensagem informativa quando o setor foi forçado para super/ceo
if ($setorForcadoParaSuper) {
    echo "<p style='color:#555; font-size:0.95em; margin-bottom:8px;'>Observação: você abriu um setor que não consta na sua lista; a visualização mostra os chamados do setor selecionado.</p>";
}
// =====================================================
// 7) CONTEÚDO PRINCIPAL — usando layout.php
// =====================================================
require_once __DIR__ . '/../config/bootstrap.php';

// Filtro simples (igual ao público)
$filtroChamado = trim($_GET['chamado'] ?? '');

// Paginação
$porPagina = intval($_GET['por_pagina'] ?? 10);
if (!in_array($porPagina, [10, 20, 50, 100])) {
    $porPagina = 10;
}

$paginaAtual = max(1, intval($_GET['pagina'] ?? 1));
$offset      = ($paginaAtual - 1) * $porPagina;

// =====================================================
// SQL BASE — agora simplificado, pois o filtro é simples
// =====================================================
$sqlBase = "
    FROM chamados ch
    LEFT JOIN lojas l ON ch.loja_origem = l.id
    LEFT JOIN funcionarios f ON ch.solicitante_id = f.id
    WHERE ch.setor_destino = ?
      AND LOWER(TRIM(ch.status)) IN (
            'aberto',
            'em andamento',
            'reaberto',
            'reaberto pelo setor',
            'aguardando avaliacao',
            'aguardando avaliação'
      )
";

$bindTypes  = 'i';
$bindValues = [$setorId];

// Filtro simples — número do chamado
if ($filtroChamado !== '') {
    $buscaEsc = $conn->real_escape_string($filtroChamado);
    $sqlBase .= " AND ch.codigo_chamado LIKE '%{$buscaEsc}%'";
}

// =====================================================
// TOTAL DE REGISTROS
// =====================================================
$sqlCount = "SELECT COUNT(*) AS total " . $sqlBase;
$stmtCount = $conn->prepare($sqlCount);

$params = array_merge([$bindTypes], $bindValues);
$refs = [];
foreach ($params as $k => $v) $refs[$k] = &$params[$k];
call_user_func_array([$stmtCount, 'bind_param'], $refs);

$stmtCount->execute();
$totalRegistros = $stmtCount->get_result()->fetch_assoc()['total'] ?? 0;
$stmtCount->close();

$totalPaginas = max(1, ceil($totalRegistros / $porPagina));

// =====================================================
// BUSCAR REGISTROS
// =====================================================
$sqlDados = "
    SELECT ch.*,
           l.nome AS loja_nome,
           f.nome AS solicitante_nome
    " . $sqlBase . "
    ORDER BY ch.data_abertura ASC
    LIMIT {$offset}, {$porPagina}
";

$stmt = $conn->prepare($sqlDados);
call_user_func_array([$stmt, 'bind_param'], $refs);
$stmt->execute();
$chamados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// =====================================================
// INÍCIO DO BUFFER — CONTEÚDO PRINCIPAL
// =====================================================
ob_start();
?>
<?php
session_start();
session_write_close();

require_once '../dados/conexao.php';
require_once '../includes/funcoes.php';
date_default_timezone_set('America/Sao_Paulo');

$conn = conectar();

// =====================================================
// 0) Verificação básica de sessão
// =====================================================
if (!isset($_SESSION['cargo'])) {
    header("Location: /login.php");
    exit;
}

$cpf           = preg_replace('/\D+/', '', (string)($_SESSION['cpf'] ?? ''));
$cargo         = strtolower(trim($_SESSION['cargo'] ?? ''));
$idFuncionario = intval($_SESSION['funcionario_id'] ?? ($_SESSION['id_funcionario'] ?? 0));

// =====================================================
// 1) Gerente/Subgerente → chamados da loja
// =====================================================
if (in_array($cargo, ['gerente', 'subgerente'], true)) {
    header("Location: chamados_loja.php");
    exit;
}

// =====================================================
// 2) Buscar setores liberados
// =====================================================
$setoresSetor = usuarioTemSetores($conn, $cpf);
if (!is_array($setoresSetor)) $setoresSetor = [];

$setoresIds   = array_map('intval', array_column($setoresSetor, 'id'));
$setoresNomes = array_map('trim', array_column($setoresSetor, 'nome'));

$isSuperOuCeo = in_array($cargo, ['super', 'ceo'], true);

// =====================================================
// 3) Permissão manual de pendências
// =====================================================
if (!$isSuperOuCeo && temAcesso($conn, $cpf, 'acesso_pendencias')) {
    $setoresSetor[] = ['id' => 0, 'nome' => 'Pendências'];
    $setoresIds[]   = 0;
    $setoresNomes[] = 'Pendências';
}

// =====================================================
// 4) Se não tiver nenhum setor → bloqueia
// =====================================================
if (empty($setoresSetor)) {
    echo "<h2>❌ Você não tem acesso a nenhum setor de pendências.</h2>";
    exit;
}

// =====================================================
// 5) Se houver mais de um setor, escolher
// =====================================================
if (count($setoresSetor) > 1 && !isset($_GET['setor'])) {

    require_once __DIR__ . '/../config/bootstrap.php';
    include ROOT_PATH . '/includes/head.php';
    include ROOT_PATH . '/includes/menu.php';
    include ROOT_PATH . '/perfil/menu_perfil.php';

    echo "<h2>📌 Escolha o setor que deseja visualizar:</h2><ul style='font-size:18px;'>";

    foreach ($setoresSetor as $s) {
        if ($isSuperOuCeo && intval($s['id']) === 0) {
            echo "<li style='color:#777;'>🔒 " . htmlspecialchars($s['nome']) . " (somente leitura)</li>";
            continue;
        }
        echo "<li><a href='?setor=" . intval($s['id']) . "'>➡️ " . htmlspecialchars($s['nome']) . "</a></li>";
    }

    echo "</ul>";
    exit;
}

// =====================================================
// 6) Definir setor final
// =====================================================
$primeiroSetorId = intval($setoresSetor[array_key_first($setoresSetor)]['id']);
$setorId = intval($_GET['setor'] ?? $primeiroSetorId);

// Buscar nome do setor
$setorNome = null;
foreach ($setoresSetor as $s) {
    if ((int)$s['id'] === $setorId) {
        $setorNome = $s['nome'];
        break;
    }
}

if ($setorNome === null && $isSuperOuCeo) {
    $stmtS = $conn->prepare("SELECT nome FROM setores WHERE id = ? LIMIT 1");
    $stmtS->bind_param("i", $setorId);
    $stmtS->execute();
    $rowS = $stmtS->get_result()->fetch_assoc();
    if ($rowS) $setorNome = $rowS['nome'];
    $stmtS->close();
}

if ($setorNome === null) {
    echo "<h2>❌ Setor inválido ou sem permissão.</h2>";
    exit;
}

// =====================================================
// 7) Filtro simples
// =====================================================
$filtroChamado = trim($_GET['chamado'] ?? '');

// Paginação
$porPagina = intval($_GET['por_pagina'] ?? 10);
if (!in_array($porPagina, [10, 20, 50, 100])) $porPagina = 10;

$paginaAtual = max(1, intval($_GET['pagina'] ?? 1));
$offset      = ($paginaAtual - 1) * $porPagina;

// =====================================================
// SQL BASE
// =====================================================
$sqlBase = "
    FROM chamados ch
    LEFT JOIN lojas l ON ch.loja_origem = l.id
    LEFT JOIN funcionarios f ON ch.solicitante_id = f.id
    WHERE ch.setor_destino = ?
      AND LOWER(TRIM(ch.status)) IN (
            'aberto','em andamento','reaberto','reaberto pelo setor',
            'aguardando avaliacao','aguardando avaliação'
      )
";

$bindTypes  = 'i';
$bindValues = [$setorId];

if ($filtroChamado !== '') {
    $buscaEsc = $conn->real_escape_string($filtroChamado);
    $sqlBase .= " AND ch.codigo_chamado LIKE '%{$buscaEsc}%'";
}

// =====================================================
// TOTAL
// =====================================================
$sqlCount = "SELECT COUNT(*) AS total " . $sqlBase;
$stmtCount = $conn->prepare($sqlCount);

$params = array_merge([$bindTypes], $bindValues);
$refs = [];
foreach ($params as $k => $v) $refs[$k] = &$params[$k];
call_user_func_array([$stmtCount, 'bind_param'], $refs);

$stmtCount->execute();
$totalRegistros = $stmtCount->get_result()->fetch_assoc()['total'] ?? 0;
$stmtCount->close();

$totalPaginas = max(1, ceil($totalRegistros / $porPagina));

// =====================================================
// BUSCAR REGISTROS
// =====================================================
$sqlDados = "
    SELECT ch.*, l.nome AS loja_nome, f.nome AS solicitante_nome
    " . $sqlBase . "
    ORDER BY ch.data_abertura ASC
    LIMIT {$offset}, {$porPagina}
";

$stmt = $conn->prepare($sqlDados);
call_user_func_array([$stmt, 'bind_param'], $refs);
$stmt->execute();
$chamados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// =====================================================
// CONTAGEM PARA AS ABAS
// =====================================================
$countPendentes = 0;
$countAguardando = 0;

foreach ($chamados as $c) {
    $status = normalizarStatus($c['status']);
    if ($status === 'aguardando avaliacao') {
        $countAguardando++;
    } else {
        $countPendentes++;
    }
}

// =====================================================
// INÍCIO DO BUFFER — CONTEÚDO PRINCIPAL
// =====================================================
ob_start();
?>

<h2>📁 Chamados do Setor: <?= htmlspecialchars($setorNome) ?></h2>
<p>Gerencie os chamados destinados ao seu setor.</p>

<!-- FILTRO -->
<form method="GET" class="filtro-form">
    <input type="hidden" name="setor" value="<?= intval($setorId) ?>">
    <div class="filtro-item">
        <label>Nº do Chamado:</label>
        <input type="text" name="chamado" placeholder="CHM-2025..." value="<?= htmlspecialchars($filtroChamado) ?>">
    </div>
    <div class="filtro-botoes">
        <button class="btn">Buscar</button>
        <a class="btn-secondary" href="chamados_setores.php?setor=<?= $setorId ?>">Limpar</a>
    </div>
</form>

<!-- ABAS -->
<div class="tabs">
    <button class="tab" aria-selected="true" data-filter="pendentes">
        Pendentes <span class="count">(<?= $countPendentes ?>)</span>
    </button>
    <button class="tab" aria-selected="false" data-filter="aguardando">
        Aguardando Avaliação <span class="count">(<?= $countAguardando ?>)</span>
    </button>
</div>

<?php
$grupos = ['pendentes' => [], 'aguardando' => []];

foreach ($chamados as $c) {
    $status = normalizarStatus($c['status']);
    if ($status === 'aguardando avaliacao') {
        $grupos['aguardando'][] = $c;
    } else {
        $grupos['pendentes'][] = $c;
    }
}
?>

<?php foreach ($grupos as $status => $lista): ?>
<div class="grupo<?= $status !== 'pendentes' ? ' hidden' : '' ?>" data-grupo="<?= $status ?>">

    <?php if (empty($lista)): ?>
        <p class="empty">Nenhum chamado nesta categoria.</p>
    <?php else: ?>
        <div class="tarefas-grid">

            <?php foreach ($lista as $c): ?>
                <?php
                $statusNorm = normalizarStatus($c['status']);
                $primeiroNome = explode(' ', trim($c['solicitante_nome']))[0] ?? '-';
                $isReaberto = in_array($statusNorm, ['reaberto', 'reaberto pelo setor']);
                ?>

                <div class="tarefa-card">

                    <div>
                        <!-- Solicitante em destaque -->
                        <div class="solicitante-topo">
                            <?= htmlspecialchars($primeiroNome) ?>
                        </div>

                        <!-- ID do chamado menor -->
                        <div class="codigo-chamado">
                            <?= htmlspecialchars($c['codigo_chamado']) ?>
                        </div>

                        <!-- Badge de reaberto -->
                        <div class="linha-status-prazo">
                            <?php if ($isReaberto): ?>
                                <span class="badge-reaberta">Reaberto pelo solicitante</span>
                            <?php endif; ?>
                        </div>

                        <!-- Descrição -->
                        <div class="descricao">
                            <small><?= nl2br(htmlspecialchars($c['descricao'])) ?></small>
                        </div>
                    </div>

                    <div class="acoes-tarefa">
                        <button class="btn-sec btn-ver-detalhes" data-id="<?= $c['id'] ?>">Ver detalhes</button>

                        <?php if (in_array($statusNorm, ['aberto','reaberto','reaberto pelo setor'])): ?>
                            <button class="btn btn-fechar-chamado" data-id="<?= $c['id'] ?>">Fechar chamado</button>
                        <?php endif; ?>
                    </div>

                </div>


            <?php endforeach; ?>

        </div>
    <?php endif; ?>

</div>
<?php endforeach; ?>

<!-- PAGINAÇÃO -->
<div class="paginacao">
    <div class="paginacao-controle">
        <?php if ($paginaAtual > 1): ?>
            <a class="btn" href="?setor=<?= $setorId ?>&pagina=<?= $paginaAtual - 1 ?>&por_pagina=<?= $porPagina ?>">⬅️ Anterior</a>
        <?php endif; ?>

        <span>Página <?= $paginaAtual ?> de <?= $totalPaginas ?></span>

        <?php if ($paginaAtual < $totalPaginas): ?>
            <a class="btn" href="?setor=<?= $setorId ?>&pagina=<?= $paginaAtual + 1 ?>&por_pagina=<?= $porPagina ?>">Próxima ➡️</a>
        <?php endif; ?>
    </div>

    <form method="GET" class="paginacao-itens">
        <input type="hidden" name="setor" value="<?= $setorId ?>">
        <label>Itens por página:</label>
        <select name="por_pagina" onchange="this.form.submit()">
            <option <?= $porPagina==10?'selected':'' ?>>10</option>
            <option <?= $porPagina==20?'selected':'' ?>>20</option>
            <option <?= $porPagina==50?'selected':'' ?>>50</option>
            <option <?= $porPagina==100?'selected':'' ?>>100</option>
        </select>
    </form>
</div>

<div class="botoes-acoes">
    <a class="btn" href="../index.php">🏠 Voltar</a>
    <a class="btn" href="chamados_encerrados_setores.php?setor=<?= $setorId ?>">📁 Encerrados</a>
</div>

<?php
$conteudo = ob_get_clean();
?>

<?php ob_start(); ?>

<!-- MODAL DETALHES -->
<div id="modalDetalhes" class="modal">
    <div class="modal-conteudo" onclick="event.stopPropagation()">
        <span class="modal-close" onclick="fecharModalDetalhes()">×</span>
        <h3>🔍 Detalhes do chamado</h3>
        <div id="modalDetalhesConteudo">Carregando...</div>
    </div>
</div>

<!-- MODAL FECHAR -->
<div id="modalFechar" class="modal">
    <div class="modal-conteudo" onclick="event.stopPropagation()">
        <span class="modal-close" onclick="fecharModalFechar()">×</span>
        <h3>Fechar chamado</h3>

        <form id="formFechar" onsubmit="enviarFechamento(event)">
            <input type="hidden" id="fecharId" name="id">

            <label><strong>Solução aplicada:</strong></label>
            <textarea id="fecharSolucao" name="solucao" rows="4" required></textarea>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="fecharModalFechar()">Cancelar</button>
                <button type="submit" class="btn">Confirmar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL REABRIR -->
<div id="modalReabrir" class="modal">
    <div class="modal-conteudo" onclick="event.stopPropagation()">
        <span class="modal-close" onclick="fecharModalReabrir()">×</span>
        <h3>Reabrir chamado</h3>

        <form id="formReabrir" onsubmit="enviarReabertura(event)">
            <input type="hidden" id="reabrirId" name="id">

            <label><strong>Motivo da reabertura:</strong></label>
            <textarea id="reabrirMotivo" name="motivo" rows="4" required></textarea>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="fecharModalReabrir()">Cancelar</button>
                <button type="submit" class="btn">Confirmar</button>
            </div>
        </form>
    </div>
</div>

<?php
$modais = ob_get_clean();

// CSS específico
$cssExtra = "/css/chamados_setores.css";

// Scripts específicos
$scripts = '<script src="/js/chamados_setores.js"></script>';

// Layout final
include ROOT_PATH . "/includes/layout.php";
