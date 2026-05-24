<?php
session_start();

require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// ===============================
// VALIDAR ID
// ===============================
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo "<p>Protocolo inválido.</p>";
    exit;
}

// ===============================
// BUSCAR DADOS DO PROTOCOLO
// ===============================
$sql = "
    SELECT 
        t.*,
        lo.nome AS loja_origem,
        ld.nome AS loja_destino,

        fs.nome AS solicitante_nome,
        fd.nome AS solicitado_nome,
        fm.nome AS motoboy_nome,
        ff.nome AS faturado_por_nome

    FROM chamados_trilho t
    LEFT JOIN lojas lo ON lo.id = t.loja_origem_id
    LEFT JOIN lojas ld ON ld.id = t.loja_destino_id
    LEFT JOIN funcionarios fs ON fs.id = t.solicitante_id
    LEFT JOIN funcionarios fd ON fd.id = t.solicitado_id
    LEFT JOIN funcionarios fm ON fm.id = t.motoboy_id
    LEFT JOIN funcionarios ff ON ff.id = t.faturado_por
    WHERE t.id = {$id}
";

$dados = $conn->query($sql)->fetch_assoc();

if (!$dados) {
    echo "<p>Protocolo não encontrado.</p>";
    exit;
}

// Normalizar status
$status = $dados['status'] === 'em_rota' ? 'Em rota' : ucfirst($dados['status']);
$tipo   = $dados['tipo'] ?? 'medicamento';

// Nome curto
function nomeCurto($nome) {
    $p = explode(' ', trim($nome));
    return count($p) <= 1 ? $nome : $p[0] . ' ' . end($p);
}


// Normalizar tipo
$tipoBruto = trim($dados['tipo']);
$tipo      = strtolower($tipoBruto);

// Mapa de títulos do Trilho
$mapaTitulos = [
    'medicamento'    => '💊 Medicamento',
    'perfumaria'     => '🧴 Perfumaria',
    'remanejamento'  => '📄 Remanejamento',
    'malote'         => '📦 Malote',
    'item'           => '📌 Item'
];

// Título final
$tituloTipo = $mapaTitulos[$tipo] ?? ucfirst($tipoBruto);
?>


<link rel="stylesheet" href="/css/chamados_trilho_detalhes.css">

<div class="detalhes-box">

    <!-- INFORMAÇÕES GERAIS -->
    <h3>📄 Informações Gerais</h3>

    <p><strong>Protocolo:</strong> <?= htmlspecialchars($dados['protocolo']) ?></p>
    <p><strong>Tipo:</strong> <?= $tituloTipo ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($status) ?></p>
    <p><strong>Data de criação:</strong> <?= date('d/m/Y H:i', strtotime($dados['data_criacao'])) ?></p>

    <hr>

    <!-- PARTICIPANTES -->
    <h3>👥 Participantes</h3>

    <p><strong>Solicitante:</strong> <?= htmlspecialchars(nomeCurto($dados['solicitante_nome'])) ?></p>
    <p><strong>Aos cuidados de:</strong> <?= htmlspecialchars(nomeCurto($dados['solicitado_nome'])) ?></p>

    <?php if (!empty($dados['faturado_por_nome'])): ?>
        <p><strong>Faturado por:</strong> <?= htmlspecialchars(nomeCurto($dados['faturado_por_nome'])) ?></p>
    <?php endif; ?>

    <?php if (!empty($dados['motoboy_nome'])): ?>
        <p><strong>Motoboy:</strong> <?= htmlspecialchars(nomeCurto($dados['motoboy_nome'])) ?></p>
    <?php endif; ?>

    <hr>

    <!-- LOGÍSTICA -->
    <h3>🚚 Logística</h3>

    <p><strong>Origem:</strong> <?= htmlspecialchars($dados['loja_origem']) ?></p>
    <p><strong>Liberação:</strong> <?= htmlspecialchars($dados['loja_destino']) ?></p>

    <?php if (!empty($dados['data_coleta'])): ?>
        <p><strong>Data da coleta:</strong> <?= date('d/m/Y H:i', strtotime($dados['data_coleta'])) ?></p>
    <?php endif; ?>

    <?php if (!empty($dados['data_entrega'])): ?>
        <p><strong>Data da entrega:</strong> <?= date('d/m/Y H:i', strtotime($dados['data_entrega'])) ?></p>
    <?php endif; ?>

    <hr>

    <!-- DETALHES DO ITEM -->
    <h3>📦 Detalhes do Item</h3>

    <p><strong>Descrição:</strong> <?= htmlspecialchars($dados['descricao']) ?></p>
    <p><strong>Quantidade:</strong> <?= intval($dados['quantidade'] ?? 1) ?></p>

    <?php if (!empty($dados['observacoes'])): ?>
        <p><strong>Observações:</strong><br><?= nl2br(htmlspecialchars($dados['observacoes'])) ?></p>
    <?php endif; ?>

    <hr>

    <!-- ASSINATURA -->
    <h3>🖊 Assinatura</h3>

    <?php if (!empty($dados['assinatura_path'])): ?>
        <p><strong>Assinatura:</strong></p>
        <img src="/<?= htmlspecialchars($dados['assinatura_path']) ?>" class="assinatura-img">
    <?php endif; ?>

    <?php if (!empty($dados['assinatura_nome'])): ?>
        <p><strong>Recebido por:</strong> <?= htmlspecialchars($dados['assinatura_nome']) ?></p>
    <?php endif; ?>

    <?php if (!empty($dados['assinatura_data'])): ?>
        <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($dados['assinatura_data'])) ?></p>
    <?php endif; ?>

    <hr>

    <!-- DOCUMENTOS -->
    <h3>🧾 Documentos</h3>

    <p><strong>Nota de transferência:</strong> 
        <?= $dados['nota_transferencia'] ? htmlspecialchars($dados['nota_transferencia']) : '—' ?>
    </p>

</div>
