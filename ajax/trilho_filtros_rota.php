<?php
session_start();
require_once __DIR__ . '/../dados/conexao.php';
$conn = conectar();

$solic = $_GET['solic'] ?? '';

$sql = "
    SELECT 
        ct.id,
        ct.protocolo,
        ct.descricao,
        ct.tipo,
        lo.nome AS origem_nome,
        ld.nome AS destino_nome
    FROM chamados_trilho ct
    LEFT JOIN lojas lo ON lo.id = ct.loja_origem_id
    LEFT JOIN lojas ld ON ld.id = ct.loja_destino_id
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
        <span class="tag-status rota">Em rota</span>
    </div>

    <div class="card-body">
        <p><strong>Solicitante:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
        <p><strong>Loja de Liberação:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
    </div>

    <div class="card-actions">
        <button class="btn-trilho btn-detalhes" data-id="<?= $c['id'] ?>">Detalhes</button>

        <a href="trilho_assinar.php?id=<?= $c['id'] ?>" 
           class="btn-trilho btn-entregar">
           Entregar
        </a>
    </div>

</div>

<?php endwhile; ?>
