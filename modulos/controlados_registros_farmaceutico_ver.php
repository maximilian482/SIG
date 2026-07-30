<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

if (!isset($_SESSION['cpf'])) {
    header("Location: /login.php");
    exit;
}

$filial = $_GET['filial'] ?? '';
if (!$filial) {
    header("Location: controlados.php");
    exit;
}

// BUSCAR FILIAL
$nomeFilialAtual = '';
if ($filial) {
    $stmt = $conn->prepare("SELECT nome FROM lojas WHERE id = ?");
    $stmt->bind_param("i", $filial);
    $stmt->execute();
    $nomeFilialAtual = $stmt->get_result()->fetch_assoc()['nome'] ?? '';
}

/* ============================
   FILTROS
============================ */
$fData      = $_GET['data']      ?? '';
$fOrcamento = $_GET['orcamento'] ?? '';   // ALTERADO
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

if ($fOrcamento) {   // ALTERADO
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
$sqlDados = "SELECT c.*
             " . $sqlBase . "
             ORDER BY c.id DESC
             LIMIT ? OFFSET ?";

$paramsDados = $params;
$typesDados  = $types . "ii";
$paramsDados[] = $limite;
$paramsDados[] = $offset;

$stmt = $conn->prepare($sqlDados);
$stmt->bind_param($typesDados, ...$paramsDados);
$stmt->execute();
$registros = $stmt->get_result();

function gerarLinkPaginacao($filial, $pagina, $limite, $filtros) {
    $params = [
        "filial" => $filial,
        "pagina" => $pagina
    ];

    if ($limite !== '') {
        $params["limite"] = $limite;
    }

    foreach (["data","orcamento","codigo","vendedor","registrado","conferido"] as $f) { // ALTERADO
        if (isset($filtros[$f]) && $filtros[$f] !== '') {
            $params[$f] = $filtros[$f];
        }
    }

    return "controlados_registros.php?" . http_build_query($params);
}

ob_start();
?>

<link rel="stylesheet" href="/css/controlados.css">

<div class="controlados-container" data-cpf="<?= preg_replace('/\D/', '', $_SESSION['cpf']) ?>">

    <h2>
        📄 Registros de Controlados 
        <?php if ($nomeFilialAtual): ?>
            — <span style="color:#555;"><?= htmlspecialchars($nomeFilialAtual) ?></span>
        <?php endif; ?>
    </h2>

    <a href="controlados_registros_farmaceutico.php?filial=<?= $filial ?>" class="btn btn-cinza">⬅ Voltar</a>

    <hr>

    <!-- FILTROS -->
    <div class="bloco filtros-compactos">
        <h3>Filtros</h3>

        <form method="GET" class="form-filtros">
            <input type="hidden" name="filial" value="<?= $filial ?>">

            <div class="linha-filtros">
                <input type="date" name="data" value="<?= htmlspecialchars($fData) ?>">

                <!-- ALTERADO: produto → orcamento -->
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

            <?php
            $nome = trim($r['vendedor']);
            $partes = explode(" ", $nome);
            $vendedorFormatado = count($partes) >= 2
                ? $partes[0] . " " . $partes[count($partes)-1]
                : $nome;
            ?>

            <tr class="linha-registro">
                <td><?= htmlspecialchars($r['produto']) ?></td>
                <td><?= htmlspecialchars($vendedorFormatado) ?></td>
                <td><?= $r['conferido'] ? '✔️' : '❌' ?></td>
                <td>
                    <button class="btn-toggle" onclick="toggleDetalhes(<?= $r['id'] ?>)">🔽</button>
                </td>
            </tr>

            <!-- DETALHES -->
            <tr id="detalhes-<?= $r['id'] ?>" class="detalhes-linha">
                <td colspan="4">
                    <div class="detalhes-box">

                        <p><strong>Data:</strong> <?= date('d/m/Y', strtotime($r['data_venda'])) ?></p>
                        <p><strong>Código:</strong> <?= htmlspecialchars($r['codigo_produto']) ?></p>

                        <!-- ALTERADO: cupom → orcamento -->
                        <p><strong>Orçamento:</strong> <?= htmlspecialchars($r['orcamento']) ?></p>

                        <p><strong>Registrado por:</strong> <?= htmlspecialchars($r['registrado_nome']) ?></p>
                        <p><strong>Vendedor:</strong> <?= htmlspecialchars($r['vendedor']) ?></p>
                        <p><strong>Produto:</strong> <?= htmlspecialchars($r['produto']) ?></p>
                        <p><strong>Lote:</strong> <?= htmlspecialchars($r['lote']) ?></p>
                        <p><strong>Quantidade:</strong> <?= $r['quantidade'] ?></p>

                        <?php if (!empty($r['observacao'])): ?>
                            <p><strong>Observação:</strong> <?= nl2br(htmlspecialchars($r['observacao'])) ?></p>
                        <?php endif; ?>

                        <p>
                            <strong>Conferido:</strong>
                            <?php if ($r['conferido']): ?>
                                <span style="color:#27ae60; font-weight:bold;">✔️ Sim</span>
                            <?php else: ?>
                                <span style="color:#e74c3c; font-weight:bold;">❌ Não</span>
                            <?php endif; ?>
                        </p>

                        <?php if ($r['conferido']): ?>
                            <p><strong>Conferido por:</strong> <?= htmlspecialchars($r['conferido_por']) ?></p>
                            <p><strong>Conferido em:</strong> <?= date('d/m/Y H:i', strtotime($r['conferido_em'])) ?></p>
                        <?php endif; ?>

                    </div>
                </td>
            </tr>

            <?php endwhile; ?>

            <?php if ($totalRegistros == 0): ?>
            <tr>
                <td colspan="4">Nenhum registro encontrado.</td>
            </tr>
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
    const linha = document.getElementById("detalhes-" + id);
    linha.classList.toggle("show");
}
</script>

<script src="/js/controlados.js"></script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
