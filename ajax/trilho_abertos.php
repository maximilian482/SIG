<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// ===============================
// VARIÁVEIS DE SESSÃO
// ===============================
$cpf          = $_SESSION['cpf'];
$funcId       = $_SESSION['funcionario_id'];
$lojaUsuario  = $_SESSION['loja'] ?? 0;

$cargo = isset($_SESSION['cargo']) ? strtolower($_SESSION['cargo']) : '';

$isMotoboy = (
    temAcesso($conn, $cpf, 'trilho_motoboy')
    && $cargo === 'trilho'
);

// ===============================
// FUNÇÕES AUXILIARES
// ===============================
function isSolicitante(array $c, int $funcId): bool {
    return intval($c['solicitante_id']) === $funcId;
}

function isMotoboyDoPedido(array $c, int $funcId): bool {
    return !empty($c['motoboy_id']) && intval($c['motoboy_id']) === $funcId;
}

// ===============================
// MAPA DE TÍTULOS (COM EMOJI)
// ===============================
$mapaTitulos = [
    'medicamento'    => '💊 Medicamento',
    'perfumaria'     => '🧴 Perfumaria',
    'remanejamento'  => '📄 Remanejamento',
    'malote'         => '📦 Malote',
    'item'           => '📌 Item'
];


// ===============================
// FILTRO
// ===============================
$lib = $_GET['lib'] ?? '';

// ===============================
// SQL
// ===============================
$sql = "
    SELECT 
        ct.*,
        lo.nome AS origem_nome,
        ld.nome AS destino_nome,
        fs.nome AS nome_solicitante,
        fd.nome AS nome_solicitado
    FROM chamados_trilho ct
    LEFT JOIN lojas lo ON lo.id = ct.loja_origem_id
    LEFT JOIN lojas ld ON ld.id = ct.loja_destino_id
    LEFT JOIN funcionarios fs ON fs.id = ct.solicitante_id
    LEFT JOIN funcionarios fd ON fd.id = ct.solicitado_id
    WHERE ct.status IN ('aberto', 'faturado')
";

if ($lib !== '') {
    $sql .= " AND ct.loja_destino_id = " . intval($lib);
}

$sql .= " ORDER BY ct.id DESC";

$res = $conn->query($sql);

// ===============================
// HTML
// ===============================
if ($res->num_rows == 0) {
    echo "<p>Nenhum protocolo pendente.</p>";
    exit;
}

while ($c = $res->fetch_assoc()):

    $solicitante = isSolicitante($c, $funcId);

    // força tipo em minúsculo para classe, mapa e regras
    $tipoBruto = trim($c['tipo']);
    $tipo      = strtolower($tipoBruto);

    // identifica se é um tipo simples (novo fluxo)
    $tipoSimples = in_array($tipo, ['remanejamento', 'malote', 'item']);

?>
   <?php if ($tipoSimples): ?>

    <!-- ============================
         CARD NOVO — TIPOS SIMPLES
         ============================ -->
    <div class="card-trilho card-simples">

        <!-- Título com emoji e cor -->
        <h4 class="tipo-titulo tipo-<?= $tipo ?>">
            <?= $mapaTitulos[$tipo] ?>
        </h4>

        <!-- Envio / Recebimento -->
        <p class="tag-acao <?= $c['acao'] ?>">
            <?= $c['acao'] === 'enviar' ? '📤 Envio' : '📥 Recebimento' ?>
        </p>

        <!-- Descrição -->
        <div class="card-produto">
            <?= htmlspecialchars($c['descricao']) ?>
        </div>

        <!-- Informações principais -->
        <div class="card-body">
            <p><strong>Origem:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
            <p><strong>Destino:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>

            <?php if ($c['acao'] === 'enviar'): ?>
                <p><strong>Aos cuidados de:</strong> <?= htmlspecialchars($c['nome_solicitado']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Ações -->
        <div class="card-actions">

    <button class="btn-trilho btn-detalhes-simples" data-id="<?= $c['id'] ?>">
        Detalhes
    </button>

    <!-- EDITAR / EXCLUIR (somente solicitante) -->
    <?php if ($solicitante): ?>
        <a href="/modulos/chamados_trilho_editar_simples.php?id=<?= $c['id'] ?>" 
           class="btn-trilho btn-editar">Editar</a>

        <button class="btn-trilho btn-excluir" data-id="<?= $c['id'] ?>">
            Excluir
        </button>
    <?php endif; ?>

    <!-- COLETAR (somente motoboy) -->
    <?php if ($isMotoboy): ?>
        <button class="btn-trilho btn-coletar" data-id="<?= $c['id'] ?>">
            Coletar
        </button>
    <?php endif; ?>

</div>


    </div>

<?php else: ?>

    <!-- ============================
         CARD ANTIGO — MEDICAMENTO / PERFUMARIA
         ============================ -->
    <div class='card-trilho'>

        <!-- TIPO DO TRILHO COM EMOJI + COR -->
        <h4 class='tipo-titulo tipo-<?= htmlspecialchars($tipo) ?>'>
            <?= $mapaTitulos[$tipo] ?? htmlspecialchars(ucfirst($tipoBruto)) ?>
        </h4>
        <br>

        <div class='card-produto'><?= htmlspecialchars($c['descricao']) ?></div>

        <div class='card-header'>
            <span class='protocolo'><?= htmlspecialchars($c['protocolo']) ?></span>

            <?php if ($c['status'] == 'aberto'): ?>
                <span class='tag-status aberto'>Aberto</span>
            <?php else: ?>
                <span class='tag-status coletar'>Liberado</span>
            <?php endif; ?>
        </div>


        <div class="card-body">

        <?php if ($c['acao'] === 'enviar'): ?>

            <p><strong>Entregar:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
            <p><strong>Liberação:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
            <p><strong>Aos cuidados de:</strong> <?= htmlspecialchars($c['nome_solicitado']) ?></p>

        <?php else: ?>

            <p><strong>Enviado por:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
            <p><strong>Recebido por:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
            <p><strong>Responsável:</strong> <?= htmlspecialchars($c['nome_solicitado'] ?: '-') ?></p>

        <?php endif; ?>

    </div>


        <div class='card-actions'>

    <!-- Detalhes -->
    <button class='btn-trilho btn-detalhes' data-id='<?= $c['id'] ?>'>Detalhes</button>

    <!-- FATURAR (Medicamento + Perfumaria) -->
    <?php if (in_array($tipo, ['medicamento', 'perfumaria']) && $c['status'] == 'aberto' && !$solicitante): ?>
        <button class='btn-trilho btn-faturar' data-id='<?= $c['id'] ?>'>Faturar</button>
    <?php endif; ?>

    <!-- COLETAR (Medicamento + Perfumaria) -->
    <?php if (in_array($tipo, ['medicamento', 'perfumaria']) && $c['status'] == 'faturado' && $isMotoboy): ?>
        <button class='btn-trilho btn-coletar' data-id='<?= $c['id'] ?>'>Coletar</button>
    <?php endif; ?>

    <!-- EDITAR / EXCLUIR (somente solicitante) -->
    <?php if ($solicitante): ?>

        <?php if (in_array($tipo, ['medicamento', 'perfumaria'])): ?>

            <!-- Medicamento/Perfumaria só pode editar se estiver ABERTO -->
            <?php if ($c['status'] === 'aberto'): ?>
                <button class="btn-trilho btn-editar" data-id="<?= $c['id'] ?>">Editar</button>
                <button class='btn-trilho btn-excluir' data-id='<?= $c['id'] ?>'>Excluir</button>
            <?php endif; ?>

        <?php else: ?>

            <!-- Tipos simples -->
<a href="/modulos/chamados_trilho_editar_simples.php?id=<?= $c['id'] ?>" class="btn-trilho btn-editar">Editar</a>
            <button class='btn-trilho btn-excluir' data-id='<?= $c['id'] ?>'>Excluir</button>

        <?php endif; ?>

    <?php endif; ?>

    <!-- COLETAR (Tipos Simples) -->
    <?php if ($tipoSimples && $isMotoboy): ?>
        <button class='btn-trilho btn-coletar' data-id='<?= $c['id'] ?>'>Coletar</button>
    <?php endif; ?>

</div>



    </div>

<?php endif; ?>

<?php endwhile; ?>



</script>
