<?php
session_start();
require_once '../includes/funcoes.php';
require_once __DIR__ . '/../config/bootstrap.php';
$conn = conectar();

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
$tipo = $dados['tipo'] ?? 'medicamento';

// Função para nome curto
function nomeCurto($nome) {
    $partes = explode(' ', trim($nome));
    if (count($partes) <= 1) return $nome;
    return $partes[0] . ' ' . end($partes);
}

?>

<link rel="stylesheet" href="/css/chamados_trilho_detalhes.css">

<div class="detalhes-box">

    <!-- ===============================
         1. INFORMAÇÕES GERAIS
    ================================ -->
    <h3>📄 Informações Gerais</h3>

    <p><strong>Protocolo:</strong> <?= htmlspecialchars($dados['protocolo']) ?></p>
    <p><strong>Tipo:</strong> <?= ucfirst(htmlspecialchars($tipo)) ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($status) ?></p>
    <p><strong>Data de criação:</strong> <?= date('d/m/Y H:i', strtotime($dados['data_criacao'])) ?></p>

    <hr>

    <!-- ===============================
         2. PARTICIPANTES
    ================================ -->
    <h3>👥 Participantes</h3>

    <p><strong>Solicitante:</strong> <?= htmlspecialchars(nomeCurto($dados['solicitante_nome'])) ?></p>

    <?php if ($tipo === 'medicamento'): ?>
        <p><strong>Solicitado para:</strong> <?= htmlspecialchars(nomeCurto($dados['solicitado_nome'])) ?></p>
    <?php else: ?>
        <p><strong>Responsável pelo item:</strong> <?= htmlspecialchars(nomeCurto($dados['solicitado_nome'])) ?></p>
    <?php endif; ?>

    <?php if (!empty($dados['faturado_por_nome'])): ?>
        <p><strong>Faturado por:</strong> <?= htmlspecialchars(nomeCurto($dados['faturado_por_nome'])) ?></p>
    <?php endif; ?>

    <?php if (!empty($dados['motoboy_nome'])): ?>
        <p><strong>Motoboy:</strong> <?= htmlspecialchars(nomeCurto($dados['motoboy_nome'])) ?></p>
    <?php endif; ?>

    <hr>

    <!-- ===============================
         3. LOGÍSTICA
    ================================ -->
    <h3>🚚 Logística</h3>

    <p><strong>Loja de Origem:</strong> <?= htmlspecialchars($dados['loja_origem']) ?></p>
    <p><strong>Loja de Destino:</strong> <?= htmlspecialchars($dados['loja_destino']) ?></p>

    <?php if (!empty($dados['data_coleta'])): ?>
        <p><strong>Data da coleta:</strong> <?= date('d/m/Y H:i', strtotime($dados['data_coleta'])) ?></p>
    <?php endif; ?>

    <?php if (!empty($dados['data_entrega'])): ?>
        <p><strong>Data da entrega:</strong> <?= date('d/m/Y H:i', strtotime($dados['data_entrega'])) ?></p>
    <?php endif; ?>

    <hr>

    <!-- ===============================
         4. DETALHES DO ITEM
    ================================ -->
    <h3>📦 Detalhes do Item</h3>

    <p><strong>Descrição:</strong> <?= htmlspecialchars($dados['descricao']) ?></p>
    <p><strong>Quantidade:</strong> <?= intval($dados['quantidade'] ?? 1) ?></p>

    <?php if (!empty($dados['observacoes'])): ?>
        <p><strong>Observações:</strong><br><?= nl2br(htmlspecialchars($dados['observacoes'])) ?></p>
    <?php endif; ?>

    <hr>

    <!-- ===============================
     5. ASSINATURA 
    =============================== -->
    <h3>🖊 Assinatura</h3>

    <?php if (!empty($dados['assinatura_path'])): ?>
        <p><strong>Assinatura:</strong></p>
        <img src="/uploads/assinaturas/<?= htmlspecialchars($dados['assinatura_path']) ?>" class="assinatura-img">
    <?php endif; ?>

    <?php if (!empty($dados['assinatura_nome'])): ?>
        <p><strong>Recebido por:</strong> <?= htmlspecialchars($dados['assinatura_nome']) ?></p>
    <?php endif; ?>

    <?php if (!empty($dados['assinatura_data'])): ?>
        <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($dados['assinatura_data'])) ?></p>
    <?php endif; ?>

    <?php if ($tipo === 'medicamento'): ?>
        <hr>
        <h3>🧾 Documentos</h3>

        <p><strong>Nota de transferência:</strong> 
            <?= $dados['nota_transferencia'] ? htmlspecialchars($dados['nota_transferencia']) : '—' ?>
        </p>
    <?php endif; ?>


</div>
