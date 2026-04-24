<?php
// /ajax/trilho_abertos.php
session_start();
ini_set('display_errors', 0);

require_once __DIR__ . '/../dados/conexao.php';
$conn = conectar();

$lib = $_GET['lib'] ?? '';
$cpf = $_SESSION['cpf'] ?? '';

// Query idêntica à do arquivo que funciona (filtra por lib)
$sql = "
    SELECT 
        ct.id,
        ct.protocolo,
        ct.descricao,
        ct.tipo,
        ct.status,
        ct.solicitante_id,
        lo.nome AS origem_nome,
        ld.nome AS destino_nome
    FROM chamados_trilho ct
    LEFT JOIN lojas lo ON lo.id = ct.loja_origem_id
    LEFT JOIN lojas ld ON ld.id = ct.loja_destino_id
    WHERE 
        (
            (ct.tipo = 'medicamento' AND ct.status IN ('aberto', 'faturado'))
            OR (ct.tipo <> 'medicamento' AND ct.status = 'faturado')
        )
";

if ($lib !== '') {
    $sql .= " AND ld.id = " . intval($lib);
}

$sql .= " ORDER BY ct.id DESC LIMIT 300";

$res = $conn->query($sql);

if (!$res) {
    echo "<p class='erro'>Erro ao consultar protocolos.</p>";
    exit;
}

if ($res->num_rows == 0) {
    echo "<p>Nenhuma transferência encontrada.</p>";
    exit;
}

$mapaTitulos = [
    'documento'   => '📄 Documento',
    'medicamento' => '💊 Medicamentos',
    'malote'      => '📦 Malote',
    'item'        => '📦 Itens Diversos',
    'nota'        => '🧾 Notas',
    'comprovante' => '🧾 Comprovantes'
];

while ($c = $res->fetch_assoc()):
?>
<div class="card-trilho">
    <h4 class="tipo-titulo"><?= $mapaTitulos[$c['tipo']] ?></h4><br>
    <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>
    <div class="card-header">
        <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>
        <?php if ($c['status'] === 'aberto'): ?>
            <span class="tag-status aguardando">Aguardando faturar</span>
        <?php elseif ($c['status'] === 'faturado'): ?>
            <span class="tag-status coletar">Liberado</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <p><strong>Solicitante:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
        <p><strong>Loja de Liberação:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
    </div>
    <div class="card-actions">
        <button class="btn-trilho btn-detalhes" data-id="<?= $c['id'] ?>">Detalhes</button>

        <?php if ($c['tipo'] === 'medicamento' && $c['status'] === 'aberto'): ?>
            <button class="btn-trilho btn-faturar" data-id="<?= $c['id'] ?>">Faturar</button>
            <?php if ($c['solicitante_id'] == $cpf): ?>
                <button class="btn-trilho btn-editar" data-id="<?= $c['id'] ?>">Editar</button>
                <button class="btn-trilho btn-excluir" data-id="<?= $c['id'] ?>">Excluir</button>
            <?php endif; ?>
        <?php elseif ($c['status'] === 'faturado'): ?>
            <button class="btn-trilho btn-coletar" data-id="<?= $c['id'] ?>">Coletar</button>
        <?php endif; ?>
    </div>
</div>
<?php
endwhile;
