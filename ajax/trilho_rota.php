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
$isMotoboy    = temAcesso($conn, $cpf, 'trilho_motoboy');

// ===============================
// FUNÇÕES AUXILIARES
// ===============================
function isMotoboyDoPedido(array $c, int $funcId): bool {
    return !empty($c['motoboy_id']) && intval($c['motoboy_id']) === $funcId;
}

// ===============================
// FILTRO
// ===============================
$solic = $_GET['solic'] ?? '';

// ===============================
// SQL — COMPLETO, IGUAL AO ABERTOS
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
    WHERE ct.status = 'em_rota'
";

if ($solic !== '') {
    $sql .= " AND ct.loja_origem_id = " . intval($solic);
}

$sql .= " ORDER BY ct.id DESC";

$res = $conn->query($sql);

// ===============================
// HTML
// ===============================
if ($res->num_rows == 0) {
    echo "<p>Nenhuma transferência em rota.</p>";
    exit;
}

// ===============================
// MAPA DE TÍTULOS — IGUAL AO ABERTOS
// ===============================
$mapaTitulos = [
    'medicamento'    => '💊 Medicamento',
    'perfumaria'     => '🧴 Perfumaria',
    'remanejamento'  => '📄 Remanejamento',
    'malote'         => '📦 Malote',
    'item'           => '📌 Item'
];

while ($c = $res->fetch_assoc()):

    // Normaliza tipo
    $tipoBruto = trim($c['tipo']);
    $tipo      = strtolower($tipoBruto);

    // Detecta tipo simples
    $tipoSimples = in_array($tipo, ['remanejamento','malote','item']);

    $motoboyDoPedido = isMotoboyDoPedido($c, $funcId);
?>

<?php if ($tipoSimples): ?>

    <!-- ============================
         CARD NOVO — TIPOS SIMPLES
         ============================ -->
    <div class="card-trilho card-simples">

        <h4 class="tipo-titulo tipo-<?= $tipo ?>">
            <?= $mapaTitulos[$tipo] ?>
        </h4>

        <p class="tag-acao <?= $c['acao'] ?>">
            <?= $c['acao'] === 'enviar' ? '📤 Envio' : '📥 Recebimento' ?>
        </p>

        <div class="card-produto">
            <?= htmlspecialchars($c['descricao']) ?>
        </div>

        <div class="card-header">
            <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>
            <span class="tag-status rota">Em rota</span>
        </div>

        <div class="card-body">
            <p><strong>Origem:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
            <p><strong>Destino:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>

            <?php if ($c['acao'] === 'enviar'): ?>
                <p><strong>Aos cuidados de:</strong> <?= htmlspecialchars($c['nome_solicitado']) ?></p>
            <?php endif; ?>
        </div>

        <div class="card-actions">

            <button class="btn-trilho btn-detalhes-simples" data-id="<?= $c['id'] ?>">Detalhes</button>

            <?php if ($motoboyDoPedido): ?>
                <button class="btn-trilho btn-entregar" data-id="<?= $c['id'] ?>">Finalizar</button>
            <?php endif; ?>

        </div>

    </div>

<?php else: ?>

    <!-- ============================
         CARD ANTIGO — MEDICAMENTO / PERFUMARIA
         ============================ -->
    <div class="card-trilho">

        <h4 class="tipo-titulo tipo-<?= $tipo ?>">
            <?= $mapaTitulos[$tipo] ?? htmlspecialchars(ucfirst($tipoBruto)) ?>
        </h4>
        <br>

        <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>

        <div class="card-header">
            <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>
            <span class="tag-status rota">Em rota</span>
        </div>

        <div class="card-body">
            <p><strong>Entregar:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
            <p><strong>Liberação:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
        </div>

        <div class="card-actions">

            <button class="btn-trilho btn-detalhes" data-id="<?= $c['id'] ?>">Detalhes</button>

            <?php if ($motoboyDoPedido): ?>
                <button class="btn-trilho btn-entregar" data-id="<?= $c['id'] ?>">Finalizar</button>
            <?php endif; ?>

        </div>

    </div>

<?php endif; ?>

<?php endwhile; ?>
