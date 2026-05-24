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

// ===============================
// FILTRO
// ===============================
$loja = $_GET['loja'] ?? '';

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
    WHERE ct.status = 'entregue'
      AND DATE(ct.assinatura_data) = CURDATE()
";

if ($loja !== '') {
    $sql .= " AND ct.loja_destino_id = " . intval($loja);
}

$sql .= " ORDER BY ct.id DESC";

$res = $conn->query($sql);

// ===============================
// HTML
// ===============================
if ($res->num_rows == 0) {
    echo "<p>Nenhuma entrega finalizada hoje.</p>";
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
?>

<?php if ($tipoSimples): ?>

    <!-- ============================
         CARD NOVO — TIPOS SIMPLES
         ============================ -->
    <div class="card-trilho card-simples entregue">

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
            <span class="tag-status entregue">Entregue</span>
        </div>

        <div class="card-body">
            <p><strong>Origem:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
            <p><strong>Destino:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
            <p><strong>Recebido por:</strong> <?= htmlspecialchars($c['assinatura_nome']) ?></p>
            <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($c['assinatura_data'])) ?></p>
        </div>

        <div class="card-actions">
            <button class="btn-trilho btn-detalhes-simples" data-id="<?= $c['id'] ?>">Detalhes</button>
        </div>

    </div>

<?php else: ?>

    <!-- ============================
         CARD ANTIGO — MEDICAMENTO / PERFUMARIA
         ============================ -->
    <div class="card-trilho entregue">

        <h4 class="tipo-titulo tipo-<?= $tipo ?>">
            <?= $mapaTitulos[$tipo] ?? htmlspecialchars(ucfirst($tipoBruto)) ?>
        </h4>
        <br>

        <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>

        <div class="card-header">
            <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>
            <span class="tag-status entregue">Entregue</span>
        </div>

        <div class="card-body">
            <p><strong>Solicitante:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
            <p><strong>Loja de Liberação:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
            <p><strong>Recebido por:</strong> <?= htmlspecialchars($c['assinatura_nome']) ?></p>
            <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($c['assinatura_data'])) ?></p>
        </div>

        <div class="card-actions">
            <button class="btn-trilho btn-detalhes" data-id="<?= $c['id'] ?>">Detalhes</button>
        </div>

    </div>

<?php endif; ?>

<?php endwhile; ?>
