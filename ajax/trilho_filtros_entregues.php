<?php
session_start();
require_once __DIR__ . '/../dados/conexao.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

$conn = conectar();

$loja = $_GET['loja'] ?? '';

$sql = "
    SELECT 
        ct.*,
        lo.nome AS origem_nome,
        ld.nome AS destino_nome
    FROM chamados_trilho ct
    LEFT JOIN lojas lo ON lo.id = ct.loja_origem_id
    LEFT JOIN lojas ld ON ld.id = ct.loja_destino_id
    WHERE ct.status = 'entregue'
      AND DATE(ct.assinatura_data) = CURDATE()
";

if ($loja !== '') {
    $sql .= " AND ct.loja_destino_id = " . intval($loja);
}

$sql .= " ORDER BY ct.id DESC";

$res = $conn->query($sql);

if ($res->num_rows == 0) {
    echo "<p>Nenhuma entrega encontrada.</p>";
    exit;
}

$mapaTitulos = [
    'medicamento'    => '💊 Medicamento',
    'perfumaria'     => '🧴 Perfumaria',
    'remanejamento'  => '📄 Remanejamento',
    'malote'         => '📦 Malote',
    'item'           => '📌 Item',
    'nota'           => '🧾 Notas',
    'comprovante'    => '🧾 Comprovantes',
    'documento'      => '📄 Documento'
];

while ($c = $res->fetch_assoc()):
    $tipo = normalizarTipo($c['tipo']);
    $tipoSimples = in_array($tipo, ['remanejamento','malote','item']);
?>
<?php if ($tipoSimples): ?>

    <div class="card-trilho card-simples entregue">

        <h4 class="tipo-titulo tipo-<?= $tipo ?>">
            <?= $mapaTitulos[$tipo] ?? htmlspecialchars(ucfirst($tipo)) ?>
        </h4>

        <p class="tag-acao <?= $c['acao'] ?>">
            <?= $c['acao'] === 'enviar' ? '📤 Envio' : '📥 Recebimento' ?>
        </p>

        <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>

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

    <div class="card-trilho entregue">

        <h4 class="tipo-titulo tipo-<?= htmlspecialchars($tipo) ?>">
            <?= $mapaTitulos[$tipo] ?? htmlspecialchars(ucfirst($tipo)) ?>
        </h4>

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
