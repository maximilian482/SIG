<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// Verifica login
if (!isset($_SESSION['cpf'])) {
    header("Location: /login.php");
    exit;
}

$cpf = $_SESSION['cpf'];

// Verifica permissão do farmacêutico
if (!temAcesso($conn, $cpf, "ferramentas_controlados_farmaceutico")) {
    echo "<h2 class='text-center text-danger mt-4'>❌ Você não tem permissão para acessar esta área.</h2>";
    exit;
}

// Buscar filial e cargo do funcionário
$stmt = $conn->prepare("SELECT loja_id, cargo_id FROM funcionarios WHERE cpf = ?");
$stmt->bind_param("s", $cpf);
$stmt->execute();
$dadosUser = $stmt->get_result()->fetch_assoc();

$filialUsuario = $dadosUser['loja_id'] ?? null;
$cargoUsuario  = $dadosUser['cargo_id'] ?? null;

// CEO = 8, SUPER = 19, ADMIN = 4
$ehAdmin = in_array($cargoUsuario, [8, 19, 4]);

// Se for admin, pode escolher a filial pela URL
if ($ehAdmin) {
    $filial = $_GET['filial'] ?? $filialUsuario;
} else {
    $filial = $filialUsuario;
}

if (!$filial) {
    echo "<h2 class='text-center text-danger'>❌ Filial não encontrada para este usuário.</h2>";
    exit;
}

// Buscar nome da filial
$stmt = $conn->prepare("SELECT nome FROM lojas WHERE id = ?");
$stmt->bind_param("i", $filial);
$stmt->execute();
$nomeFilial = $stmt->get_result()->fetch_assoc()['nome'] ?? '';

/* ============================================================
   FILTRO: ORÇAMENTO
   ============================================================ */
$fOrcamento = $_GET['orcamento'] ?? '';

$whereOrcamento = "";
if ($fOrcamento !== "") {
    $orcEsc = $conn->real_escape_string($fOrcamento);
    $whereOrcamento = " AND orcamento LIKE '%$orcEsc%' ";
}

/* ============================================================
   PAGINAÇÃO + ORDENAÇÃO
   ============================================================ */
$limite = isset($_GET['limite']) ? max(1, intval($_GET['limite'])) : 10;
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina - 1) * $limite;

$ordem = $_GET['ordem'] ?? 'desc';
$ordemSQL = ($ordem === 'asc') ? 'ASC' : 'DESC';

// Contar total de pendentes
$sqlCount = "
    SELECT COUNT(*) AS total
    FROM controlados
    WHERE filial_id = $filial
      AND conferido = 0
      $whereOrcamento
";
$totalRegistros = $conn->query($sqlCount)->fetch_assoc()['total'];
$totalPaginas = ceil($totalRegistros / $limite);

// Buscar registros
$sql = "
    SELECT *
    FROM controlados
    WHERE filial_id = $filial
      AND conferido = 0
      $whereOrcamento
    ORDER BY id $ordemSQL
    LIMIT $offset, $limite
";
$registros = $conn->query($sql);

ob_start();
?>

<link rel="stylesheet" href="/css/controlados.css">

<?php if (isset($_SESSION['flash'])): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    mostrarMensagem("<?= $_SESSION['flash']['mensagem'] ?>", "<?= $_SESSION['flash']['tipo'] ?>");
});
</script>
<?php unset($_SESSION['flash']); endif; ?>

<h2 class="mb-3">
    💊 Controlados — Farmacêutico  
    <br><small class="text-muted">Filial: <?= htmlspecialchars($nomeFilial) ?></small>
</h2>

<?php if ($ehAdmin): ?>
<div class="bloco mb-3">
    <form method="GET" class="row g-3">
        <input type="hidden" name="pagina" value="1">

        <div class="col-md-4">
            <label class="form-label"><strong>Selecionar Filial:</strong></label>
            <select name="filial" class="form-select" onchange="this.form.submit()">
                <option value="">Selecionar...</option>

                <?php
                $filiais = $conn->query("
                    SELECT id, nome 
                    FROM lojas 
                    WHERE nome NOT IN ('CAV', 'ESCRITÓRIO', 'CD')
                    ORDER BY nome ASC
                ");

                while ($f = $filiais->fetch_assoc()):
                ?>
                    <option value="<?= $f['id'] ?>" <?= ($filial == $f['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['nome']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="acoes-topo mb-3">
    <a href="/modulos/ferramentas.php" class="btn btn-secondary">⬅️ Voltar</a>
    <a href="controlados_registros_farmaceutico_ver.php?filial=<?= $filial ?>" class="btn btn-primary">📄 Ver Registros</a>
</div>

<div class="bloco">
    <h3>
        Registros Pendentes de Conferência: 
        <span class="text-danger fw-bold"><?= $totalRegistros ?></span>
    </h3>

    <!-- Ordenação + Orçamento -->
    <form method="GET" class="form-ordenacao row g-3 mb-3">
        <input type="hidden" name="pagina" value="1">

        <div class="col-md-3">
            <label class="form-label">Organizar por:</label>
            <select name="ordem" class="form-select" onchange="this.form.submit()">
                <option value="desc" <?= $ordem === 'desc' ? 'selected' : '' ?>>Mais novo</option>
                <option value="asc" <?= $ordem === 'asc' ? 'selected' : '' ?>>Mais antigo</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">Orçamento:</label>
            <input type="text" name="orcamento" value="<?= htmlspecialchars($fOrcamento) ?>" class="form-control" placeholder="Número do orçamento">
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100">🔍 Buscar</button>
        </div>
    </form>

    <?php if ($totalRegistros == 0): ?>
        <p class="text-center text-success fs-5 py-3">
            ✔️ Nenhum registro pendente. Tudo conferido!
        </p>
    <?php endif; ?>

    <table class="table table-striped table-hover tabela-mobile">
        <thead>
            <tr>
                <th>Produto</th>
                <th>Vendedor</th>
                <th></th>
            </tr>
        </thead>

        <tbody>
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
            <td>
                <button class="btn btn-outline-secondary btn-sm" onclick="toggleDetalhes(<?= $r['id'] ?>)">🔽</button>
            </td>
        </tr>

        <tr id="detalhes-<?= $r['id'] ?>" class="detalhes-linha">
            <td colspan="3">
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

                    <div class="acoes-detalhes mt-3">
                        <a href="controlados_registros_farmaceutico_conferir.php?id=<?= $r['id'] ?>&filial=<?= $filial ?>"
                           class="btn btn-success"
                           onclick="return confirmarConferencia();">
                           ✔️ Conferir
                        </a>
                    </div>

                </div>
            </td>
        </tr>

        <?php endwhile; ?>
        </tbody>
    </table>

    <!-- PAGINAÇÃO -->
    <div class="paginacao d-flex justify-content-between align-items-center mt-3">

        <div class="grupo-botoes">
            <?php if ($pagina > 1): ?>
                <a class="btn btn-secondary" 
                   href="?pagina=<?= $pagina-1 ?>&ordem=<?= $ordem ?>&orcamento=<?= $fOrcamento ?>&limite=<?= $limite ?>">⬅ Anterior</a>
            <?php endif; ?>

            <?php if ($pagina < $totalPaginas): ?>
                <a class="btn btn-primary" 
                   href="?pagina=<?= $pagina+1 ?>&ordem=<?= $ordem ?>&orcamento=<?= $fOrcamento ?>&limite=<?= $limite ?>">Próxima ➡</a>
            <?php endif; ?>
        </div>

        <div class="info-pagina">
            Página <?= $pagina ?> de <?= $totalPaginas ?>
        </div>

        <div>
            <label><strong>Mostrar:</strong></label>
            <select class="form-select d-inline-block w-auto"
                    onchange="window.location='?pagina=1&ordem=<?= $ordem ?>&orcamento=<?= $fOrcamento ?>&limite='+this.value">
                <option value="10" selected>10</option>
                <option value="20">20</option>
                <option value="30">30</option>
                <option value="50">50</option>
            </select>
        </div>

    </div>

</div>

<script>
function toggleDetalhes(id) {
    const linha = document.getElementById("detalhes-" + id);
    linha.classList.toggle("show");
}

function confirmarConferencia() {
    return confirm("⚠️ VOCÊ VAI MARCAR ESTE REGISTRO COMO CONFERIDO.\n\n❗ ESSA AÇÃO NÃO PODE SER DESFEITA.\n\nTem certeza disso?");
}
</script>

<script src="/js/controlados.js?v=<?= time() ?>"></script>

<?php if (isset($_SESSION['flash'])): ?>
<script>
    mostrarMensagem("<?= $_SESSION['flash']['mensagem'] ?>", "<?= $_SESSION['flash']['tipo'] ?>");
</script>
<?php unset($_SESSION['flash']); endif; ?>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
