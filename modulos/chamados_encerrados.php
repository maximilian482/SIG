<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

$usuario      = $_SESSION['usuario'];
$lojaUsuario  = intval($_SESSION['loja'] ?? 0);
$nomeUsuario  = $_SESSION['nome'] ?? $usuario;
$usuarioId    = intval($_SESSION['funcionario_id'] ?? 0);

// Bootstrap + ROOT_PATH
$bootstrapPath = __DIR__ . '/../config/bootstrap.php';
if (file_exists($bootstrapPath)) {
    require_once $bootstrapPath;
}
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(__DIR__ . '/..'));
}

// ===============================
// BUSCAR NOME DA LOJA DO USUÁRIO
// ===============================
$lojaNome = $conn->query("SELECT nome FROM lojas WHERE id = $lojaUsuario")
                 ->fetch_assoc()['nome'] ?? 'Sua Loja';

// ===============================
// FILTROS
// ===============================
$filtroSetor = $_GET['setor'] ?? '';
$filtroId    = trim($_GET['id'] ?? '');

$paginaAtual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$porPagina   = 10;
$inicio      = ($paginaAtual - 1) * $porPagina;

// ===============================
// BUSCAR SETORES DINAMICAMENTE
// ===============================
$setores = $conn->query("SELECT nome FROM setores ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);

// ===============================
// WHERE DINÂMICO
// ===============================
$params = [];
$types  = "";

$where = "(c.loja_origem = ? OR c.loja_destino = ?) AND LOWER(c.status) = 'encerrado'";
$params[] = $lojaUsuario;
$params[] = $lojaUsuario;
$types   .= "ii";

// Filtro por ID do chamado
if ($filtroId !== "") {
    $where .= " AND c.codigo_chamado = ?";
    $params[] = $filtroId;
    $types   .= "s";
}

// Filtro por setor
if (!empty($filtroSetor)) {
    $where .= " AND c.setor_destino = ?";
    $params[] = $filtroSetor;
    $types   .= "s";
}

// ===============================
// CONSULTA PRINCIPAL
// ===============================
$query = "
    SELECT 
        c.id,
        c.codigo_chamado,
        c.loja_origem,
        c.data_abertura,
        c.data_solucao,
        c.avaliacao,
        c.nota_estrelas,
        lo.nome AS nome_loja_origem,
        f_resp.nome AS nome_responsavel
    FROM chamados c
    LEFT JOIN lojas lo ON lo.id = c.loja_origem
    LEFT JOIN funcionarios f_resp ON f_resp.id = c.responsavel_id
    WHERE $where
    ORDER BY c.data_solucao DESC
    LIMIT ?, ?
";

$params[] = $inicio;
$params[] = $porPagina;
$types   .= "ii";

$stmt = $conn->prepare($query);

if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$chamados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ===============================
// TOTAL PARA PAGINAÇÃO
// ===============================
$paramsTotal = array_slice($params, 0, count($params) - 2);
$typesTotal  = substr($types, 0, strlen($types) - 2);

$stmtTotal = $conn->prepare("SELECT COUNT(*) AS total FROM chamados c WHERE $where");

if (!empty($typesTotal)) {
    $stmtTotal->bind_param($typesTotal, ...$paramsTotal);
}

$stmtTotal->execute();
$totalChamados = intval($stmtTotal->get_result()->fetch_assoc()['total'] ?? 0);

// ===============================
// FUNÇÃO TEMPO
// ===============================
function tempoAbertoStr(?string $dataAbertura, ?string $dataSolucao): string {
    if (!$dataAbertura || !$dataSolucao) return '—';
    $tsA = strtotime($dataAbertura);
    $tsS = strtotime($dataSolucao);
    if (!$tsA || !$tsS) return '—';
    $diff  = $tsS - $tsA;
    $dias  = floor($diff / 86400);
    $horas = floor(($diff % 86400) / 3600);
    $min   = floor(($diff % 3600) / 60);
    return $dias > 0 ? "{$dias}d {$horas}h" : ($horas > 0 ? "{$horas}h {$min}m" : "{$min}m");
}

// ===============================
// CONTEÚDO (layout.php)
// ===============================
ob_start();
?>

<link rel="stylesheet" href="/css/chamados_publico.css">

<h2>📁 Chamados Encerrados — <?= htmlspecialchars($lojaNome) ?></h2>
<p>Visualize os chamados encerrados da sua loja.</p>

<!-- ===============================
     FILTROS
=============================== -->
<form method="GET" class="filtro-form">

    <div>
        <label for="id">🔍 Buscar por ID:</label>
        <input type="text" name="id" id="id" placeholder="CHM-2025..." value="<?= htmlspecialchars($filtroId) ?>">
    </div>

    <div>
        <label for="setor">Setor:</label>
        <select name="setor" id="setor">
            <option value="">Todos</option>
            <?php foreach ($setores as $s): ?>
                <option value="<?= $s['nome'] ?>" <?= ($filtroSetor === $s['nome']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button class="btn">Filtrar</button>
    <a href="chamados_encerrados.php" class="btn btn-limpar">🧹 Limpar</a>

</form>

<!-- ===============================
     TABELA
=============================== -->
<div class="tabela-container">
<table>
    <tr>
        <th>Código</th>
        <th>Loja origem</th>
        <th>Tempo aberto</th>
        <th>Nota</th>
        <th>Resolvido por</th>
        <th>Ações</th>
    </tr>

    <?php if (empty($chamados)): ?>
        <tr><td colspan="6" style="text-align:center;">Nenhum chamado encerrado encontrado.</td></tr>
    <?php else: ?>
        <?php foreach ($chamados as $c): ?>
            <?php
                $tempoAberto = tempoAbertoStr($c['data_abertura'], $c['data_solucao']);
                $nota = intval($c['nota_estrelas'] ?? 0);
                $notaStr = (!empty($c['avaliacao']) && $c['avaliacao'] === 'Sim' && $nota > 0)
                    ? str_repeat('⭐', $nota) . " ({$nota})"
                    : '—';
                $responsavel = $c['nome_responsavel'] ?? '—';
            ?>
            <tr>
                <td><?= htmlspecialchars($c['codigo_chamado']) ?></td>
                <td><?= htmlspecialchars($c['nome_loja_origem'] ?? '—') ?></td>
                <td><?= htmlspecialchars($tempoAberto) ?></td>
                <td><?= htmlspecialchars($notaStr) ?></td>
                <td><?= htmlspecialchars($responsavel) ?></td>
                <td>
                    <button type="button" class="btn" onclick="abrirDetalhes(<?= (int)$c['id'] ?>)"> 🔍 </button>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>
</div>

<!-- ===============================
     PAGINAÇÃO
=============================== -->
<?php
$totalPaginas = max(1, ceil($totalChamados / $porPagina));
if ($totalPaginas > 1):
?>
<div class="paginacao">
    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
        <a href="?pagina=<?= $i ?>&id=<?= urlencode($filtroId) ?>&setor=<?= urlencode($filtroSetor) ?>"
           class="<?= $i == $paginaAtual ? 'ativo' : '' ?>">
           <?= $i ?>
        </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<div class="botoes-acoes">
    <a class="btn" href="chamados_publico.php">🔙 Voltar</a>
</div>

<!-- ===============================
     MODAL DE DETALHES
=============================== -->
<div id="modalDetalhes" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>Detalhes do Chamado</h3>
        <div id="conteudoDetalhes">Carregando...</div>
        <button class="btn" type="button" onclick="fecharDetalhes()">Fechar</button>
    </div>
</div>

<?php
$conteudo = ob_get_clean();
$modais = "";

// ===============================
// SCRIPTS ESPECÍFICOS DA PÁGINA
// ===============================
$scripts = '<script src="/js/chamados_encerrados.js"></script>';

include ROOT_PATH . '/includes/layout.php';

