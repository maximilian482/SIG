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

$idFuncionario = $_SESSION['funcionario_id'] ?? null;
$cargo         = strtolower($_SESSION['cargo'] ?? '');
$cpf           = $_SESSION['cpf'] ?? '';
$loja          = $_SESSION['loja'] ?? null;

/* ============================================================
   NOVA LÓGICA DE PERMISSÕES (SEM setorDoFuncionario)
   Gestão tem acesso total aos detalhes
============================================================ */

$isGerenciaLoja = in_array($cargo, ['gerente', 'subgerente']);
$isSuperOuCeo   = in_array($cargo, ['super', 'ceo']);

// Setores liberados
$setoresLiberados = usuarioTemSetores($conn, $cpf);

// SUPER/CEO → sempre setor Diretoria
if ($isSuperOuCeo && !in_array('Diretoria', $setoresLiberados)) {
    $setoresLiberados[] = 'Diretoria';
}

/* ============================================================
   BUSCAR DADOS DO CHAMADO
============================================================ */

$stmt = $conn->prepare("
    SELECT c.*, 
           f.nome AS nome_solicitante,
           lo.nome AS nome_loja_origem,
           ld.nome AS nome_loja_destino
    FROM chamados c
    LEFT JOIN funcionarios f ON f.id = c.solicitante_id
    LEFT JOIN lojas lo ON lo.id = c.loja_origem
    LEFT JOIN lojas ld ON ld.id = c.loja_destino
    WHERE c.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$chamado = $stmt->get_result()->fetch_assoc();

if (!$chamado) {
    echo "<p style='color:red;'>Chamado não encontrado.</p>";
    exit;
}

$statusNorm = normalizarStatus($chamado['status']);

/* ============================================================
   HISTÓRICO
============================================================ */

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

/* ============================================================
   FORMATADOR DE DATA
============================================================ */

function fmt($d) {
    return $d ? date('d/m/Y H:i', strtotime($d)) : '—';
}

$classeStatus = 'status-badge status-' . str_replace(' ', '-', $statusNorm);
?>

<link rel="stylesheet" href="../css/chamados_detalhes.css">

<div class="detalhes-container">

    <!-- ============================
         COLUNA ESQUERDA — DADOS
    ============================= -->
    <div class="detalhes-info">
        <h3>📄 Dados do Chamado</h3>

        <p><strong>ID:</strong> <?= htmlspecialchars($chamado['codigo_chamado'] ?? $chamado['id']) ?></p>
        <p><strong>Título:</strong> <?= htmlspecialchars($chamado['titulo']) ?></p>
        <p><strong>Descrição:</strong><br><?= nl2br(htmlspecialchars($chamado['descricao'])) ?></p>

        <p><strong>Destino:</strong>
            <?= $chamado['setor_destino'] 
                ? "Setor: " . htmlspecialchars($chamado['setor_destino']) 
                : "Loja: " . htmlspecialchars($chamado['nome_loja_destino']) ?>
        </p>

        <p><strong>Loja origem:</strong> <?= htmlspecialchars($chamado['nome_loja_origem']) ?></p>

        <p><strong>Status:</strong> 
            <span class="<?= $classeStatus ?>"><?= ucfirst($statusNorm) ?></span>
        </p>

        <p><strong>Abertura:</strong> <?= fmt($chamado['data_abertura']) ?></p>

        <?php if ($chamado['data_solucao']): ?>
            <p><strong>Data solução:</strong> <?= fmt($chamado['data_solucao']) ?></p>
        <?php endif; ?>

        <?php if ($chamado['avaliacao']): ?>
            <p><strong>Avaliação:</strong> 
                <?php if ($chamado['avaliacao'] === "Sim"): ?>
                    <?php for ($i = 1; $i <= intval($chamado['nota_estrelas']); $i++): ?>
                        ⭐
                    <?php endfor; ?>
                    (<?= intval($chamado['nota_estrelas']) ?>)
                <?php else: ?>
                    ❌ Não aprovado
                <?php endif; ?>
            </p>

            <?php if ($chamado['avaliacao'] === "Não"): ?>
                <p><strong>Motivo da não aprovação:</strong><br><?= nl2br(htmlspecialchars($chamado['justificativa'])) ?></p>
            <?php endif; ?>

            <p><strong>Data avaliação:</strong> <?= fmt($chamado['data_avaliacao']) ?></p>
        <?php endif; ?>

        <p><strong>Solicitante:</strong> <?= htmlspecialchars($chamado['nome_solicitante']) ?></p>
    </div>

    <!-- ============================
         COLUNA DIREITA — TIMELINE
    ============================= -->
    <div class="detalhes-timeline">
        <h3>💬 Histórico</h3>

        <div class="timeline-box">

            <?php if (empty($historico)): ?>
                <p class="sem-historico">Nenhuma interação registrada ainda.</p>
            <?php else: ?>
                <?php foreach ($historico as $h): ?>
                    <div class="msg <?= $h['respondido_por'] == $idFuncionario ? 'msg-eu' : 'msg-outro' ?>">
                        <div class="msg-nome">
                            <?= htmlspecialchars($h['respondido_por_nome'] ?? 'Sistema') ?>
                            <small style="color:#777;">(<?= ucfirst($h['tipo']) ?>)</small>
                        </div>
                        <div class="msg-texto">
                            <?= nl2br(htmlspecialchars($h['resposta'])) ?>
                        </div>
                        <div class="msg-data">
                            <?= fmt($h['data']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>

        <!-- Gestão: apenas leitura, sem formulários -->
    </div>

</div>
