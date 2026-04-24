<?php
// /ajax/trilho_rota.php
session_start();
ini_set('display_errors', 0);

require_once __DIR__ . '/../dados/conexao.php';
$conn = conectar();

$sol = $_GET['sol'] ?? '';
$cpf = $_SESSION['cpf'] ?? '';

// Em rota: status = 'em_rota' — filtra por loja destino (onde deve ser entregue) ou por solicitante conforme sua regra
$where = " WHERE ct.status = 'em_rota' ";

if ($sol !== '') {
    // aqui assumimos que o select da aba rota envia o id da loja destino (ou solicitante conforme sua UI)
    $where .= " AND ct.loja_destino_id = " . intval($sol);
}

$sql = "
SELECT 
    ct.id,
    ct.protocolo,
    ct.descricao,
    ct.tipo,
    ct.status,
    lo.nome AS origem_nome,
    ld.nome AS destino_nome
FROM chamados_trilho ct
LEFT JOIN lojas lo ON lo.id = ct.loja_origem_id
LEFT JOIN lojas ld ON ld.id = ct.loja_destino_id
{$where}
ORDER BY ct.id DESC
LIMIT 300
";

$res = $conn->query($sql);

if (!$res) {
    echo "<p class='erro'>Erro ao consultar protocolos em rota.</p>";
    exit;
}

if ($res->num_rows === 0) {
    echo "<p>Nenhum protocolo em rota.</p>";
    exit;
}

while ($c = $res->fetch_assoc()):
?>
<div class="card-trilho">
    <div class="card-header">
        <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>
        <span class="tag-status <?= htmlspecialchars($c['status']) ?>"><?= ucfirst($c['status']) ?></span>
    </div>

    <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>

    <div class="card-body">
        <p><strong>Origem:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
        <p><strong>Entrega em:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
    </div>

    <div class="card-actions">
        <button class="btn-trilho btn-detalhes" data-id="<?= $c['id'] ?>">Detalhes</button>
        <button class="btn-trilho btn-entregar" data-id="<?= $c['id'] ?>">Finalizar Entrega</button>
    </div>
</div>
<?php
endwhile;
