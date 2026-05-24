<?php
session_start();
require_once __DIR__ . '/../dados/conexao.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

$conn = conectar();

$solic = $_GET['solic'] ?? '';

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
    $sql .= " AND lo.id = " . intval($solic);
}

$sql .= " ORDER BY ct.id DESC";

$res = $conn->query($sql);

if ($res->num_rows == 0) {
    echo "<p>Nenhuma transferência em rota.</p>";
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

    <div class="card-trilho card-simples">

        <h4 class="tipo-titulo tipo-<?= $tipo ?>">
            <?= $mapaTitulos[$tipo] ?? htmlspecialchars(ucfirst($tipo)) ?>
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
        </div>

        <div class="card-actions">
            <button class="btn-trilho btn-detalhes-simples" data-id="<?= $c['id'] ?>">Detalhes</button>
            <a href="trilho_assinar.php?id=<?= $c['id'] ?>" class="btn-trilho btn-entregar">Entregar</a>
        </div>

    </div>

<?php else: ?>

    <div class="card-trilho">

        <h4 class="tipo-titulo tipo-<?= htmlspecialchars($tipo) ?>">
            <?= $mapaTitulos[$tipo] ?? htmlspecialchars(ucfirst($tipo)) ?>
        </h4>
        <br>

        <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>

        <div class="card-header">
            <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>
            <span class="tag-status rota">Em rota</span>
        </div>

        <div class="card-body">

            <?php if ($c['acao'] === 'enviar'): ?>

                <p><strong>Entregar:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
                <p><strong>Liberação:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
                <p><strong>Aos cuidados de:</strong> <?= htmlspecialchars($c['nome_solicitado'] ?? '-') ?></p>

            <?php else: ?>

                <p><strong>Enviado por:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
                <p><strong>Recebido por:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
                <p><strong>Responsável:</strong> <?= htmlspecialchars($c['nome_solicitado'] ?? '-') ?></p>

            <?php endif; ?>

        </div>

        <div class="card-actions">
            <button class="btn-trilho btn-detalhes" data-id="<?= $c['id'] ?>">Detalhes</button>
            <a href="trilho_assinar.php?id=<?= $c['id'] ?>" class="btn-trilho btn-entregar">Entregar</a>
        </div>

    </div>

<?php endif; ?>

<?php endwhile; ?>
