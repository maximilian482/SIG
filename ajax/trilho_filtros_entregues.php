<?php
session_start();
require_once __DIR__ . '/../dados/conexao.php';
$conn = conectar();

$loja = $_GET['loja'] ?? '';

$sql = "
    SELECT 
        ct.id,
        ct.protocolo,
        ct.descricao,
        ct.tipo,
        lo.nome AS origem_nome,
        ld.nome AS destino_nome,
        ct.assinatura_nome,
        ct.assinatura_data
    FROM chamados_trilho ct
    LEFT JOIN lojas lo ON lo.id = ct.loja_origem_id
    LEFT JOIN lojas ld ON ld.id = ct.loja_destino_id
    WHERE ct.status = 'entregue'
      AND DATE(ct.assinatura_data) = CURDATE()
";

if ($loja !== '') {
    // FILTRAR PELA LOJA QUE RECEBEU A ENTREGA
    $sql .= " AND ct.loja_destino_id = " . intval($loja);
}

$sql .= " ORDER BY ct.id DESC";

$res = $conn->query($sql);

if ($res->num_rows == 0) {
    echo "<p>Nenhuma entrega encontrada.</p>";
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

<div class="card-trilho entregue">

    <h4 class="tipo-titulo"><?= $mapaTitulos[$c['tipo']] ?></h4><br>

    <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>

    <div class="card-header">
        <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>
        <span class="tag-status entregue">Entregue</span>
    </div>

    <div class="card-body">
        <p><strong>Loja de liberação:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
        <p><strong>Entregar na Loja:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
        <p><strong>Recebido por:</strong> <?= htmlspecialchars($c['assinatura_nome']) ?></p>
        <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($c['assinatura_data'])) ?></p>
    </div>

    <div class="card-actions">
        <button class="btn-trilho btn-detalhes" data-id="<?= $c['id'] ?>">Detalhes</button>
    </div>

</div>

<?php endwhile; ?>
