<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

if (!isset($_SESSION['cpf'])) {
    header("Location: /login.php");
    exit;
}

$cpfLogado = preg_replace('/\D/', '', $_SESSION['cpf']);

$filial = $_GET['filial'] ?? '';
if (!$filial) {
    header("Location: controlados.php");
    exit;
}

// BUSCAR FILIAL
$stmt = $conn->prepare("SELECT nome FROM lojas WHERE id = ?");
$stmt->bind_param("i", $filial);
$stmt->execute();
$nomeFilialAtual = $stmt->get_result()->fetch_assoc()['nome'] ?? '';

/* ============================
   FILTROS
============================ */
$fData      = $_GET['data']      ?? '';
$fOrcamento = $_GET['orcamento'] ?? '';
$fCod       = $_GET['codigo']    ?? '';
$fVend      = $_GET['vendedor']  ?? '';
$fReg       = $_GET['registrado']?? '';
$fConf      = $_GET['conferido'] ?? '';

$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$limite = isset($_GET['limite']) ? intval($_GET['limite']) : 10;
$limite = in_array($limite, [10,20,30,50]) ? $limite : 10;
$offset = ($pagina - 1) * $limite;

/* ============================
   SQL BASE
============================ */
$sqlBase = "FROM controlados c WHERE c.filial_id = ?";
$params  = [$filial];
$types   = "i";

if ($fData) {
    $sqlBase .= " AND c.data_venda = ?";
    $params[] = $fData;
    $types   .= "s";
}

if ($fOrcamento) {
    $sqlBase .= " AND c.orcamento LIKE ?";
    $params[] = "%$fOrcamento%";
    $types   .= "s";
}

if ($fCod) {
    $sqlBase .= " AND c.codigo_produto LIKE ?";
    $params[] = "%$fCod%";
    $types   .= "s";
}

if ($fVend) {
    $sqlBase .= " AND c.vendedor LIKE ?";
    $params[] = "%$fVend%";
    $types   .= "s";
}

if ($fReg) {
    $sqlBase .= " AND c.registrado_nome LIKE ?";
    $params[] = "%$fReg%";
    $types   .= "s";
}

if ($fConf !== '' && ($fConf === '0' || $fConf === '1')) {
    $sqlBase .= " AND c.conferido = ?";
    $params[] = intval($fConf);
    $types   .= "i";
}

/* ============================
   TOTAL DE REGISTROS
============================ */
$sqlCount = "SELECT COUNT(*) AS total " . $sqlBase;
$stmt = $conn->prepare($sqlCount);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totalRegistros = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

$totalPaginas = max(1, ceil($totalRegistros / $limite));

if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
    $offset = ($pagina - 1) * $limite;
}

/* ============================
   BUSCA REGISTROS
============================ */
$sqlDados = "SELECT c.* " . $sqlBase . " ORDER BY c.id DESC LIMIT ? OFFSET ?";
$paramsDados = $params;
$typesDados  = $types . "ii";
$paramsDados[] = $limite;
$paramsDados[] = $offset;

$stmt = $conn->prepare($sqlDados);
$stmt->bind_param($typesDados, ...$paramsDados);
$stmt->execute();
$registros = $stmt->get_result();

/* ============================
   FUNÇÃO PAGINAÇÃO
============================ */
function gerarLinkPaginacao($filial, $pagina, $limite, $filtros) {
    $params = [
        "filial" => $filial,
        "pagina" => $pagina
    ];

    if ($limite !== '') {
        $params["limite"] = $limite;
    }

    foreach (["data","orcamento","codigo","vendedor","registrado","conferido"] as $f) {
        if (isset($filtros[$f]) && $filtros[$f] !== '') {
            $params[$f] = $filtros[$f];
        }
    }

    return "controlados_registros.php?" . http_build_query($params);
}

ob_start();
?>

<link rel="stylesheet" href="/css/controlados.css">

<div class="controlados-container">

    <h2>
        📄 Registros de Controlados — <?= htmlspecialchars($nomeFilialAtual) ?>
    </h2>

    <a href="controlados.php?filial=<?= $filial ?>" class="btn btn-cinza">⬅ Voltar</a>

    <hr>

    <!-- FILTROS -->
    <div class="bloco filtros-compactos">
        <h3>Filtros</h3>

        <form method="GET">
            <input type="hidden" name="filial" value="<?= $filial ?>">

            <div class="linha-filtros">
                <input type="date" name="data" value="<?= htmlspecialchars($fData) ?>">
                <input type="text" name="orcamento" value="<?= htmlspecialchars($fOrcamento) ?>" placeholder="Orçamento">
            </div>

            <div class="linha-filtros">
                <input type="text" name="codigo" value="<?= htmlspecialchars($fCod) ?>" placeholder="Código do Produto">
                <input type="text" name="vendedor" value="<?= htmlspecialchars($fVend) ?>" placeholder="Vendedor">
            </div>

            <div class="linha-filtros">
                <input type="text" name="registrado" value="<?= htmlspecialchars($fReg) ?>" placeholder="Registrado por">
                <select name="conferido">
                    <option value="">Conferido?</option>
                    <option value="1" <?= $fConf==='1'?'selected':'' ?>>Sim</option>
                    <option value="0" <?= $fConf==='0'?'selected':'' ?>>Não</option>
                </select>
            </div>

            <div class="botoes-filtros">
                <button class="btn">🔍 Buscar</button>
                <a href="controlados_registros.php?filial=<?= $filial ?>" class="btn btn-cinza">🧹 Limpar</a>
            </div>
        </form>
    </div>

    <!-- TABELA -->
    <div class="bloco">
        <h3>Registros Encontrados (<?= $totalRegistros ?>)</h3>

        <table class="tabela-mobile">
            <tr>
                <th>Produto</th>
                <th>Vendedor</th>
                <th>Conf.</th>
                <th></th>
            </tr>

            <?php while ($r = $registros->fetch_assoc()): ?>

            <tr class="linha-registro">
                <td><?= htmlspecialchars($r['produto']) ?></td>
                <td><?= htmlspecialchars($r['vendedor']) ?></td>
                <td><?= $r['conferido'] ? '✔️' : '❌' ?></td>
                <td><button class="btn-toggle" onclick="toggleDetalhes(<?= $r['id'] ?>)">🔽</button></td>
            </tr>

            <tr id="detalhes-<?= $r['id'] ?>" class="detalhes-linha">
                <td colspan="4">
                    <div class="detalhes-box">

                        <p><strong>Data:</strong> <?= date('d/m/Y', strtotime($r['data_venda'])) ?></p>
                        <p><strong>Código:</strong> <?= htmlspecialchars($r['codigo_produto']) ?></p>
                        <p><strong>Orçamento:</strong> <?= htmlspecialchars($r['orcamento']) ?></p>
                        <p><strong>Registrado por:</strong> <?= htmlspecialchars($r['registrado_nome']) ?></p>
                        <p><strong>Vendedor:</strong> <?= htmlspecialchars($r['vendedor']) ?></p>
                        <p><strong>Produto:</strong> <?= htmlspecialchars($r['produto']) ?></p>
                        <p><strong>Lote:</strong> <?= htmlspecialchars($r['lote']) ?></p>
                        <p><strong>Quantidade:</strong> <?= $r['quantidade'] ?></p>

                        <?php if (!empty($r['observacao'])): ?>
                            <p><strong>Observação:</strong> <?= nl2br(htmlspecialchars($r['observacao'])) ?></p>
                        <?php endif; ?>

                        <div class="acoes-detalhes">

                            <a href="controlados_registros_editar.php?id=<?= $r['id'] ?>&filial=<?= $filial ?>"
                                class="btn-acao editar"
                                data-registrado="<?= trim($r['registrado_por']) ?>">
                                ✏️ Editar
                            </a>

                            <a href="controlados_registros_excluir.php?id=<?= $r['id'] ?>&filial=<?= $filial ?>"
                                class="btn-acao excluir"
                                data-registrado="<?= trim($r['registrado_por']) ?>">
                                🗑️ Excluir
                            </a>

                        </div>

                    </div>
                </td>
            </tr>

            <?php endwhile; ?>

            <?php if ($totalRegistros == 0): ?>
            <tr><td colspan="4">Nenhum registro encontrado.</td></tr>
            <?php endif; ?>

        </table>

        <!-- PAGINAÇÃO -->
        <div class="paginacao">

            <div class="grupo-botoes">
                <?php if ($pagina > 1): ?>
                    <a class="btn btn-cinza" 
                       href="<?= gerarLinkPaginacao($filial, $pagina-1, $limite, $_GET) ?>">⬅ Anterior</a>
                <?php endif; ?>

                <?php if ($pagina < $totalPaginas): ?>
                    <a class="btn" 
                       href="<?= gerarLinkPaginacao($filial, $pagina+1, $limite, $_GET) ?>">Próxima ➡</a>
                <?php endif; ?>
            </div>

            <div class="info-pagina">
                Página <?= $pagina ?> de <?= $totalPaginas ?>
            </div>

            <div>
                <label><strong>Mostrar:</strong></label>
                <select onchange="window.location='<?= gerarLinkPaginacao($filial, 1, '' , $_GET) ?>&limite='+this.value">
                    <option value="10" <?= $limite==10?'selected':'' ?>>10</option>
                    <option value="20" <?= $limite==20?'selected':'' ?>>20</option>
                    <option value="30" <?= $limite==30?'selected':'' ?>>30</option>
                    <option value="50" <?= $limite==50?'selected':'' ?>>50</option>
                </select>
            </div>

        </div>

    </div>

</div>

<script>
function toggleDetalhes(id) {
    document.getElementById("detalhes-" + id).classList.toggle("show");
}
</script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
