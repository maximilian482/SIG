<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

if (!isset($_SESSION['usuario'])) {
    echo "<p style='color:red;'>Sessão expirada. Faça login novamente.</p>";
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo "<p style='color:red;'>Chamado inválido.</p>";
    exit;
}

$idFuncionario = intval($_SESSION['funcionario_id'] ?? 0);
$cargo = strtolower($_SESSION['cargo'] ?? '');
$cpf   = $_SESSION['cpf'] ?? '';
$loja  = intval($_SESSION['loja'] ?? 0);

$isGerenciaLoja = in_array($cargo, ['gerente', 'subgerente'], true);
$isSuperOuCeo   = in_array($cargo, ['super', 'ceo'], true);

$setoresRaw = usuarioTemSetores($conn, $cpf);
$setoresIds = array_map('intval', array_column($setoresRaw, 'id'));

if ($isSuperOuCeo) {
    $setoresIds = [];
    $res = $conn->query("SELECT id FROM setores");
    while ($r = $res->fetch_assoc()) {
        $setoresIds[] = intval($r['id']);
    }
}

$stmt = $conn->prepare("
    SELECT c.*, 
           f.nome AS nome_solicitante,
           lo.nome AS nome_loja_origem,
           ld.nome AS nome_loja_destino,
           s.nome AS nome_setor_destino
    FROM chamados c
    LEFT JOIN funcionarios f ON f.id = c.solicitante_id
    LEFT JOIN lojas lo ON lo.id = c.loja_origem
    LEFT JOIN lojas ld ON ld.id = c.loja_destino
    LEFT JOIN setores s ON s.id = c.setor_destino
    WHERE c.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$chamado = $stmt->get_result()->fetch_assoc();

if (!$chamado) {
    echo "<p style='color:red;'>Chamado não encontrado.</p>";
    exit;
}

$chamado['setor_destino']  = intval($chamado['setor_destino'] ?? 0);
$chamado['loja_destino']   = intval($chamado['loja_destino'] ?? 0);
$chamado['solicitante_id'] = intval($chamado['solicitante_id'] ?? 0);

$statusNorm = normalizarStatus($chamado['status']);

$stmt = $conn->prepare("
    SELECT r.*, f.nome AS respondido_por_nome
    FROM respostas_chamados r
    LEFT JOIN funcionarios f ON f.id = r.respondido_por
    WHERE r.chamado_id = ?
    ORDER BY r.data ASC
");
$stmt->bind_param("i", $id);
$stmt->execute();
$historico = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function fmt($d) {
    return $d ? date('d/m/Y H:i', strtotime($d)) : '—';
}

$classeStatus = 'status-' . str_replace(' ', '-', $statusNorm);
?>
<link rel="stylesheet" href="../css/chamados_detalhes.css">

<div class="detalhes-container">

    <div class="detalhes-info">
        <h3>📄 Dados do Chamado</h3>

        <p><strong>ID:</strong> <?= htmlspecialchars($chamado['codigo_chamado']) ?></p>
        <p><strong>Título:</strong> <?= htmlspecialchars($chamado['titulo']) ?></p>
        <p><strong>Descrição:</strong><br><?= nl2br(htmlspecialchars($chamado['descricao'])) ?></p>

        <p><strong>Destino:</strong>
            <?= $chamado['setor_destino'] > 0 
                ? "Setor: " . htmlspecialchars($chamado['nome_setor_destino'])
                : "Loja: " . htmlspecialchars($chamado['nome_loja_destino']) ?>
        </p>

        <p><strong>Loja origem:</strong> <?= htmlspecialchars($chamado['nome_loja_origem']) ?></p>

        <p><strong>Status:</strong>
            <span class="status-badge <?= $classeStatus ?>"><?= ucfirst($statusNorm) ?></span>
        </p>

        <p><strong>Abertura:</strong> <?= fmt($chamado['data_abertura']) ?></p>

        <?php if (!empty($chamado['data_solucao'])): ?>
            <p><strong>Data solução:</strong> <?= fmt($chamado['data_solucao']) ?></p>
        <?php endif; ?>

        <?php if (!empty($chamado['avaliacao'])): ?>
            <p><strong>Avaliação:</strong>
                <?= $chamado['avaliacao'] === "Sim"
                    ? str_repeat("⭐", intval($chamado['nota_estrelas'])) . " ({$chamado['nota_estrelas']})"
                    : "❌ Não aprovado" ?>
            </p>

            <?php if ($chamado['avaliacao'] === "Não"): ?>
                <p><strong>Motivo:</strong><br><?= nl2br(htmlspecialchars($chamado['justificativa'])) ?></p>
            <?php endif; ?>

            <p><strong>Data avaliação:</strong> <?= fmt($chamado['data_avaliacao']) ?></p>
        <?php endif; ?>

        <p><strong>Solicitante:</strong> <?= htmlspecialchars($chamado['nome_solicitante']) ?></p>
    </div>

    <div class="detalhes-timeline">
        <h3>💬 Histórico</h3>

        <div class="timeline-box">

            <?php if (empty($historico)): ?>
                <p class="sem-historico">Nenhuma interação registrada ainda.</p>
            <?php else: ?>
                <?php foreach ($historico as $h): ?>
                    <div class="msg <?= intval($h['respondido_por']) === $idFuncionario ? 'msg-eu' : 'msg-outro' ?>">
                        <div class="msg-nome">
                            <?= htmlspecialchars($h['respondido_por_nome'] ?? 'Sistema') ?>
                            <small>(<?= htmlspecialchars(ucfirst($h['tipo'])) ?>)</small>
                        </div>
                        <div class="msg-texto"><?= nl2br(htmlspecialchars($h['resposta'])) ?></div>
                        <div class="msg-data"><?= fmt($h['data']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>

</div>
