<?php
// /ajax/trilho_entregues.php
session_start();
ini_set('display_errors', 0);

require_once __DIR__ . '/../dados/conexao.php';
$conn = conectar();

$ent = $_GET['ent'] ?? '';
$cpf = $_SESSION['cpf'] ?? '';

// Entregues do dia (como no seu exemplo)
$where = " WHERE ct.status = 'entregue' AND DATE(ct.assinatura_data) = CURDATE() ";

if ($ent !== '') {
    $where .= " AND ct.loja_destino_id = " . intval($ent);
}

$sql = "
SELECT 
    ct.id,
    ct.protocolo,
    ct.descricao,
    ct.tipo,
    ct.status,
    ct.assinatura_nome,
    ct.assinatura_data,
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
    echo "<p class='erro'>Erro ao consultar entregues.</p>";
    exit;
}

if ($res->num_rows === 0) {
    echo "<p>Nenhuma entrega hoje.</p>";
    exit;
}

while ($c = $res->fetch_assoc()):
    $assinatura_data = $c['assinatura_data'] ? date('d/m/Y H:i', strtotime($c['assinatura_data'])) : '-';
?>
<div class="card-trilho">
    <div class="card-header">
        <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>
        <span class="tag-status entregue">Entregue</span>
    </div>

    <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>

    <div class="card-body">
        <p><strong>Origem:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
        <p><strong>Entregue em:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
        <p><strong>Recebido por:</strong> <?= htmlspecialchars($c['assinatura_nome'] ?? '-') ?></p>
        <p><strong>Data:</strong> <?= $assinatura_data ?></p>
    </div>

    <div class="card-actions">
        <button class="btn-trilho btn-detalhes" data-id="<?= $c['id'] ?>">Detalhes</button>
    </div>
</div>
<?php
endwhile;
