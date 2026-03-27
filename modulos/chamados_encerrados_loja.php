<?php
session_start();
require_once '../dados/conexao.php';
require_once '../includes/funcoes.php';
require_once __DIR__ . '/../config/bootstrap.php';

$conn = conectar();

// ===============================
// VALIDAR LOGIN E ACESSO
// ===============================
if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

$usuario     = $_SESSION['usuario'];
$lojaId      = intval($_SESSION['loja'] ?? 0);
$nomeUsuario = $_SESSION['nome'] ?? $usuario;
$usuarioId   = intval($_SESSION['funcionario_id'] ?? 0);
$cargo       = strtolower($_SESSION['cargo'] ?? '');
$cpf         = $_SESSION['cpf'] ?? '';

$temAcesso = in_array($cargo, ['gerente', 'subgerente'])
             || temAcesso($conn, $cpf, 'acesso_painel_loja');

if (!$temAcesso || $lojaId === 0) {
    $conteudo = "<h3>Acesso restrito à gerência ou responsável autorizado da unidade.</h3>";
    $modais = "";
    $scripts = "";
    include ROOT_PATH . '/includes/layout.php';
    exit;
}

// ===============================
// BUSCAR NOME DA LOJA
// ===============================
$nomeLoja = $conn->query("SELECT nome FROM lojas WHERE id = {$lojaId}")
                 ->fetch_assoc()['nome'] ?? 'Loja não definida';

// ===============================
// FILTROS
// ===============================
$filtroId    = trim($_GET['id'] ?? '');
$paginaAtual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$porPagina   = 10;
$inicio      = ($paginaAtual - 1) * $porPagina;

// ===============================
// WHERE — PRIORIDADE PARA BUSCA POR ID
// ===============================
$params = [];
$types  = "";

if ($filtroId !== "") {
    $where = "c.loja_destino = ? AND c.codigo_chamado = ? AND LOWER(c.status) = 'encerrado'";
    $params[] = $lojaId;
    $params[] = $filtroId;
    $types   .= "is";
} else {
    $where = "c.loja_destino = ? AND LOWER(c.status) = 'encerrado'";
    $params[] = $lojaId;
    $types   .= "i";
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
$stmt->bind_param($types, ...$params);
$stmt->execute();
$chamados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ===============================
// TOTAL PARA PAGINAÇÃO
// ===============================
$paramsTotal = array_slice($params, 0, count($params) - 2);
$typesTotal  = substr($types, 0, strlen($types) - 2);

$stmtTotal = $conn->prepare("SELECT COUNT(*) AS total FROM chamados c WHERE $where");
$stmtTotal->bind_param($typesTotal, ...$paramsTotal);
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
// CONTEÚDO PRINCIPAL
// ===============================
ob_start();
?>

<link rel="stylesheet" href="../css/chamados_encerrados.css">

<h2>📁 Chamados Encerrados — Loja <?= htmlspecialchars($nomeLoja) ?></h2>
<p>Visualize os chamados encerrados destinados à sua loja.</p>

<form method="GET" class="filtro-form">
    <div>
        <label for="id">🔍 Buscar por ID:</label>
        <input type="text" name="id" id="id" placeholder="CHM-2025..." value="<?= htmlspecialchars($filtroId) ?>">
    </div>

    <button class="btn">Filtrar</button>
    <a href="chamados_encerrados_loja.php" class="btn btn-limpar">🧹 Limpar</a>
</form>

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
                    <button type="button" class="btn" onclick="abrirDetalhesEncerrado(<?= (int)$c['id'] ?>)">🔍</button>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>
</div>

<?php
$totalPaginas = max(1, ceil($totalChamados / $porPagina));
if ($totalPaginas > 1):
?>
<div class="paginacao">
    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
        <a href="?pagina=<?= $i ?>&id=<?= urlencode($filtroId) ?>"
           class="<?= $i == $paginaAtual ? 'ativo' : '' ?>">
           <?= $i ?>
        </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<div class="botoes-acoes">
    <a class="btn" href="chamados_loja.php">🔙 Voltar</a>
</div>

<?php
$conteudo = ob_get_clean();

// ===============================
// MODAIS
// ===============================
ob_start();
?>

<div id="modalDetalhesEncerrado" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>Detalhes do Chamado</h3>
        <div id="conteudoDetalhesEncerrado">Carregando...</div>
        <button class="btn" type="button" onclick="fecharDetalhesEncerrado()">Fechar</button>
    </div>
</div>

<?php
$modais = ob_get_clean();

// ===============================
// SCRIPTS INLINE (SEM CONFLITO)
// ===============================
ob_start();
?>
<script>
console.log("JS ENCERRADOS LOJA carregado");

function abrirDetalhesEncerrado(id) {
    console.log("abrirDetalhesEncerrado chamado com id:", id);

    const modal = document.getElementById("modalDetalhesEncerrado");
    const conteudo = document.getElementById("conteudoDetalhesEncerrado");

    if (!modal || !conteudo) {
        console.error("Modal ou conteúdo não encontrados no DOM.");
        return;
    }

    modal.removeAttribute("hidden");
    modal.style.visibility = "visible";
    modal.style.opacity = "1";
    modal.style.display = "flex";

    conteudo.innerHTML = "Carregando...";

    fetch("chamados_detalhes.php?id=" + encodeURIComponent(id))
        .then(r => r.text())
        .then(html => conteudo.innerHTML = html)
        .catch(err => {
            console.error(err);
            conteudo.innerHTML = "Erro ao carregar detalhes.";
        });
}

function fecharDetalhesEncerrado() {
    const modal = document.getElementById("modalDetalhesEncerrado");
    if (modal) modal.style.display = "none";
}

document.addEventListener("keydown", e => {
    if (e.key === "Escape") fecharDetalhesEncerrado();
});

window.addEventListener("click", e => {
    const modal = document.getElementById("modalDetalhesEncerrado");
    if (modal && e.target === modal) fecharDetalhesEncerrado();
});
</script>
<?php
$scripts = ob_get_clean();

include ROOT_PATH . '/includes/layout.php';
