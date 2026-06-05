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
    echo "<h2 style='color:red; text-align:center; margin-top:40px;'>❌ Você não tem permissão para acessar esta área.</h2>";
    exit;
}

// Buscar filial e cargo do funcionário
$stmt = $conn->prepare("SELECT loja_id, cargo_id FROM funcionarios WHERE cpf = ?");
$stmt->bind_param("s", $cpf);
$stmt->execute();
$dadosUser = $stmt->get_result()->fetch_assoc();

$filialUsuario = $dadosUser['loja_id'] ?? null;
$cargoUsuario  = $dadosUser['cargo_id'] ?? null;

// CEO = 8, SUPER = 19
$ehAdmin = in_array($cargoUsuario, [8, 19]);

// Se for admin, pode escolher a filial pela URL
if ($ehAdmin) {
    $filial = $_GET['filial'] ?? $filialUsuario;
} else {
    // Funcionário comum: sempre sua própria filial
    $filial = $filialUsuario;
}

if (!$filial) {
    echo "<h2 style='color:red; text-align:center;'>❌ Filial não encontrada para este usuário.</h2>";
    exit;
}


// Buscar nome da filial
$stmt = $conn->prepare("SELECT nome FROM lojas WHERE id = ?");
$stmt->bind_param("i", $filial);
$stmt->execute();
$nomeFilial = $stmt->get_result()->fetch_assoc()['nome'] ?? '';

/* ============================================================
   FILTRO: CUPOM
   ============================================================ */
$fCupom = $_GET['cupom'] ?? '';

$whereCupom = "";
if ($fCupom !== "") {
    $cupomEsc = $conn->real_escape_string($fCupom);
    $whereCupom = " AND cupom LIKE '%$cupomEsc%' ";
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
      $whereCupom
";
$totalRegistros = $conn->query($sqlCount)->fetch_assoc()['total'];
$totalPaginas = ceil($totalRegistros / $limite);

// Buscar registros
$sql = "
    SELECT *
    FROM controlados
    WHERE filial_id = $filial
      AND conferido = 0
      $whereCupom
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

<h2>💊 Controlados — Farmacêutico  
    <br><small style="color:#555;">Filial: <?= htmlspecialchars($nomeFilial) ?></small>
</h2>

<?php if ($ehAdmin): ?>

<div class="bloco" style="margin-bottom:20px;">
    <form method="GET">
        <input type="hidden" name="pagina" value="1">

        <label><strong>Selecionar Filial:</strong></label>

        <select name="filial" onchange="this.form.submit()">
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
    </form>
</div>

<?php endif; ?>

<div class="acoes-topo">
    <a href="/modulos/ferramentas.php" class="btn btn-cinza">⬅️ Voltar</a>
    <a href="controlados_registros_farmaceutico_ver.php?filial=<?= $filial ?>" class="btn btn-azul">📄 Ver Registros</a>
</div>

<div class="bloco">
    <h3>Registros Pendentes de Conferência: 
        <span style="color:#c0392b; font-weight:bold;"><?= $totalRegistros ?></span>
    </h3>

    <!-- Ordenação + Cupom -->
    <form method="GET" class="form-ordenacao">
        <input type="hidden" name="pagina" value="1">

        <label>Organizar por:</label>
        <select name="ordem" onchange="this.form.submit()">
            <option value="desc" <?= $ordem === 'desc' ? 'selected' : '' ?>>Mais novo</option>
            <option value="asc" <?= $ordem === 'asc' ? 'selected' : '' ?>>Mais antigo</option>
        </select>

        <label style="margin-left:20px;">Cupom:</label>
        <input type="text" name="cupom" value="<?= htmlspecialchars($fCupom) ?>" placeholder="Número do cupom" style="width:140px;">
        
        <button class="btn" style="margin-left:10px;">🔍 Buscar</button>
    </form>

    <?php if ($totalRegistros == 0): ?>
        <p style="padding:20px; text-align:center; font-size:18px; color:#27ae60;">
            ✔️ Nenhum registro pendente. Tudo conferido!
        </p>
    <?php endif; ?>

    <table class="tabela-mobile">
        <tr>
            <th>Produto</th>
            <th>Vendedor</th>
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
            <td>
                <button class="btn-toggle" onclick="toggleDetalhes(<?= $r['id'] ?>)">🔽</button>
            </td>
        </tr>

        <tr id="detalhes-<?= $r['id'] ?>" class="detalhes-linha">
            <td colspan="3">
                <div class="detalhes-box">

                   <p><strong>Data:</strong> <?= date('d/m/Y', strtotime($r['data_venda'])) ?></p>
                    <p><strong>Código:</strong> <?= htmlspecialchars($r['codigo_produto']) ?></p>
                    <p><strong>Orçamento (Confirmar se não é número de cupom):</strong> <?= htmlspecialchars($r['cupom']) ?></p>
                    <p><strong>Registrado por:</strong> <?= htmlspecialchars($r['registrado_nome']) ?></p>
                    <p><strong>Vendedor:</strong> <?= htmlspecialchars($r['vendedor']) ?></p>
                    <p><strong>Produto:</strong> <?= htmlspecialchars($r['produto']) ?></p>
                    <p><strong>Lote:</strong> <?= htmlspecialchars($r['lote']) ?></p>
                    <p><strong>Quantidade:</strong> <?= $r['quantidade'] ?></p>

                    <?php if (!empty($r['observacao'])): ?>
                        <p><strong>Observação:</strong> <?= nl2br(htmlspecialchars($r['observacao'])) ?></p>
                    <?php endif; ?>

                    <div class="acoes-detalhes">
                        <a href="controlados_registros_farmaceutico_conferir.php?id=<?= $r['id'] ?>&filial=<?= $filial ?>"
                        class="btn btn-conferir"
                        onclick="return confirmarConferencia();">
                        ✔️ Conferir
                        </a>
                    </div>


                </div>
            </td>
        </tr>

        <?php endwhile; ?>

    </table>

    <!-- PAGINAÇÃO PREMIUM -->
    <div class="paginacao">

        <div class="grupo-botoes">
            <?php if ($pagina > 1): ?>
               <a class="btn btn-cinza" 
                href="?pagina=<?= $pagina-1 ?>&ordem=<?= $ordem ?>&cupom=<?= $fCupom ?>&limite=<?= $limite ?>">⬅ Anterior</a>
                <?php endif; ?>

            <?php if ($pagina < $totalPaginas): ?>
                    <a class="btn" 
                    href="?pagina=<?= $pagina+1 ?>&ordem=<?= $ordem ?>&cupom=<?= $fCupom ?>&limite=<?= $limite ?>">Próxima ➡</a>
                    <?php endif; ?>
        </div>

        <div class="info-pagina">
            Página <?= $pagina ?> de <?= $totalPaginas ?>
        </div>

        <div>
            <label><strong>Mostrar:</strong></label>
            <select onchange="window.location='?pagina=1&ordem=<?= $ordem ?>&cupom=<?= $fCupom ?>&limite='+this.value">
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
