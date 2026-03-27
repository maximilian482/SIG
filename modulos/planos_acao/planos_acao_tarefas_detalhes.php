<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';

$conn = conectar();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo "<p>Tarefa inválida.</p>";
    exit;
}

$sql = "SELECT * FROM tarefas_plano WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$t = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$t) {
    echo "<p>Tarefa não encontrada.</p>";
    exit;
}

$responsavel = resolverResponsavel($conn, $t);
$prazo = calcularPrazoClasse($t['data_limite']);

// Buscar histórico
$sqlH = "SELECT rt.*, u.nome AS usuario_nome
         FROM respostas_tarefas rt
         LEFT JOIN funcionarios u ON u.id = rt.usuario_id
         WHERE rt.id_tarefa = ?
         ORDER BY rt.criado_em ASC";

$stmtH = $conn->prepare($sqlH);
$stmtH->bind_param("i", $id);
$stmtH->execute();
$historico = $stmtH->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtH->close();
?>

<link rel="stylesheet" href="/css/planos_acao_tarefa_detalhes.css">

<div class="detalhes-container">

    <!-- ========================= -->
    <!-- BLOCO DE INFORMAÇÕES -->
    <!-- ========================= -->
    <div class="detalhes-info">

        <h3>📄 Detalhes da Tarefa</h3>

        <!-- Cabeçalho igual ao modelo dos chamados -->
        <div class="detalhes-header">
            <div class="detalhes-solicitante">
                <?= htmlspecialchars($responsavel) ?>
            </div>

            <div class="detalhes-id">
                Tarefa #<?= intval($t['id']) ?>
            </div>
        </div>

        <p><strong>Título:</strong> <?= htmlspecialchars($t['titulo']) ?></p>

        <p><strong>Descrição:</strong><br>
            <span class="detalhes-descricao">
                <?= nl2br(htmlspecialchars($t['descricao'])) ?>
            </span>
        </p>

        <p><strong>Responsável:</strong> <?= htmlspecialchars($responsavel) ?></p>

        <p><strong>Data limite:</strong>
            <?= $t['data_limite'] ? date('d/m/Y', strtotime($t['data_limite'])) : '-' ?>
        </p>

        <p><strong>Prazo:</strong>
            <span class="prazo-pill <?= $prazo['class'] ?>">
                <?= $prazo['label'] ?>
            </span>
        </p>

        <p><strong>Status:</strong>
            <?= formatarStatusTarefa($t['status']) ?>
        </p>

    </div>

    <!-- ========================= -->
    <!-- HISTÓRICO (TIMELINE) -->
    <!-- ========================= -->
    <div class="detalhes-timeline">
        <h3>💬 Histórico</h3>

        <div class="timeline-box">

            <?php if (empty($historico)): ?>
                <p class="sem-historico">Nenhuma interação registrada ainda.</p>
            <?php else: ?>
                <?php foreach ($historico as $h): ?>
                    <div class="msg <?= intval($h['usuario_id']) === intval($_SESSION['funcionario_id']) ? 'msg-eu' : 'msg-outro' ?>">

                        <div class="msg-nome">
                            <?= htmlspecialchars($h['usuario_nome'] ?? 'Sistema') ?>
                            <small>(<?= htmlspecialchars(ucfirst($h['tipo'])) ?>)</small>
                        </div>

                        <div class="msg-texto">
                            <?= nl2br(htmlspecialchars($h['mensagem'])) ?>
                        </div>

                        <div class="msg-data">
                            <?= date('d/m/Y H:i', strtotime($h['criado_em'])) ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>

</div>
