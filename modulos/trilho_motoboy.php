<?php
session_start();

require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// Verifica login
if (!isset($_SESSION['cpf'])) {
    header("Location: /login.php");
    exit;
}

$cpf = $_SESSION['cpf'];

// Verifica permissão
if (!temAcesso($conn, $cpf, 'trilho_motoboy')) {
    $conteudo = "<h2 style='color:red; text-align:center; margin-top:40px;'>❌ Você não tem permissão para acessar o Trilho.</h2>";
    include ROOT_PATH . '/includes/layout.php';
    exit;
}

// ===============================
// CONTEÚDO DA PÁGINA
// ===============================
ob_start();
?>

<link rel="stylesheet" href="/css/trilho_motoboy.css">

<div class="trilho-acoes-topo">
    <a href="/modulos/chamados_publico.php" class="btn-voltar">⬅ Voltar</a>
    <a href="trilho_motoboy_historico.php" class="btn-historico">📁 Histórico</a>
</div>

<h2 class="titulo-trilho">🚚 Trilho — Motoboy</h2>

<!-- ABAS -->
<div class="abas-trilho">
    <button class="aba ativa" data-aba="coletar">Coletar</button>
    <button class="aba" data-aba="rota">Em rota</button>
    <button class="aba" data-aba="entregues">Entregues</button>
</div>

<!-- ============================
     ABA: COLETAR
============================ -->
<div id="coletar" class="conteudo-aba ativo">
    <h3>📦 Transferências para Coletar</h3>

    <?php
    $sql = "
        SELECT 
            ct.id,
            ct.protocolo,
            ct.descricao,
            ct.status,
            lo.nome AS origem_nome,
            ld.nome AS destino_nome
        FROM chamados_trilho ct
        LEFT JOIN lojas lo ON lo.id = ct.loja_origem_id
        LEFT JOIN lojas ld ON ld.id = ct.loja_destino_id
        WHERE ct.status IN ('aberto', 'faturado')
        ORDER BY ct.id DESC
    ";

    $res = $conn->query($sql);

    if ($res->num_rows == 0): ?>
        <p>Nenhuma transferência pendente.</p>
    <?php else: ?>
        <?php while ($c = $res->fetch_assoc()): ?>

            <div class="card-trilho">

                <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>

                <div class="card-header">
                    <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>

                    <?php if ($c['status'] === 'aberto'): ?>
                        <span class="tag-status aberto">Aberto</span>
                    <?php else: ?>
                        <span class="tag-status faturado">Faturado</span>
                    <?php endif; ?>
                </div>

                <div class="card-body">
                    <p><strong>Loja Solicitada:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
                    <p><strong>Loja de entrega:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
                </div>

                <div class="card-actions">

                    <!-- Detalhes via modal -->
                    <button class="btn-trilho btn-detalhes" data-id="<?= $c['id'] ?>">Detalhes</button>

                    <?php if ($c['status'] === 'faturado'): ?>
                        <button class="btn-trilho btn-coletar" data-id="<?= $c['id'] ?>">Coletar</button>
                    <?php else: ?>
                        <span class="aguardando">Aguardando faturamento</span>
                    <?php endif; ?>

                </div>

            </div>

        <?php endwhile; ?>
    <?php endif; ?>
</div>

<!-- ============================
     ABA: EM ROTA
============================ -->
<div id="rota" class="conteudo-aba">
    <h3>🛵 Transferências em Rota</h3>

    <?php
    $sql2 = "
        SELECT 
            ct.id,
            ct.protocolo,
            ct.descricao,
            lo.nome AS origem_nome,
            ld.nome AS destino_nome
        FROM chamados_trilho ct
        LEFT JOIN lojas lo ON lo.id = ct.loja_origem_id
        LEFT JOIN lojas ld ON ld.id = ct.loja_destino_id
        WHERE ct.status = 'em_rota'
        ORDER BY ct.id DESC
    ";

    $res2 = $conn->query($sql2);

    if ($res2->num_rows == 0): ?>
        <p>Nenhuma transferência em rota.</p>
    <?php else: ?>
        <?php while ($c = $res2->fetch_assoc()): ?>

            <div class="card-trilho">

                <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>

                <div class="card-header">
                    <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>
                    <span class="tag-status rota">Em rota</span>
                </div>

                <div class="card-body">
                    <p><strong>Origem:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
                    <p><strong>Destino:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
                </div>

                <div class="card-actions">

                    <!-- Detalhes via modal -->
                    <button class="btn-trilho btn-detalhes" data-id="<?= $c['id'] ?>">Detalhes</button>

                    <!-- Entregar -->
                    <a href="trilho_assinar.php?id=<?= $c['id'] ?>" 
                       class="btn-trilho btn-entregar">
                       Entregar
                    </a>

                </div>

            </div>

        <?php endwhile; ?>
    <?php endif; ?>
</div>

<!-- ============================
     ABA: ENTREGUES
============================ -->
<div id="entregues" class="conteudo-aba">
    <h3>📄 Entregues (Hoje)</h3>

    <?php
    $sql3 = "
        SELECT 
            ct.id,
            ct.protocolo,
            ct.descricao,
            lo.nome AS origem_nome,
            ld.nome AS destino_nome,
            ct.assinatura_nome,
            ct.assinatura_data
        FROM chamados_trilho ct
        LEFT JOIN lojas lo ON lo.id = ct.loja_origem_id
        LEFT JOIN lojas ld ON ld.id = ct.loja_destino_id
        WHERE ct.status = 'entregue'
          AND DATE(ct.assinatura_data) = CURDATE()
        ORDER BY ct.id DESC
    ";

    $res3 = $conn->query($sql3);

    if ($res3->num_rows == 0): ?>
        <p>Nenhuma entrega finalizada hoje.</p>
        <p><a href="trilho_historico.php" class="btn-historico">📚 Ver histórico completo</a></p>
    <?php else: ?>
        <?php while ($c = $res3->fetch_assoc()): ?>

            <div class="card-trilho entregue">

                <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>

                <div class="card-header">
                    <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>
                    <span class="tag-status entregue">Entregue</span>
                </div>

                <div class="card-body">
                    <p><strong>Loja Solicitada:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
                    <p><strong>Loja de entrega:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
                    <p><strong>Recebido por:</strong> <?= htmlspecialchars($c['assinatura_nome']) ?></p>
                    <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($c['assinatura_data'])) ?></p>
                </div>

                <div class="card-actions">

                    <!-- Detalhes via modal -->
                    <button class="btn-trilho btn-detalhes" data-id="<?= $c['id'] ?>">Detalhes</button>

                </div>

            </div>

        <?php endwhile; ?>
    <?php endif; ?>
</div>

<!-- MODAL -->
<div id="modalDetalhes" class="modal-trilho">
    <div class="modal-conteudo">
        <span class="modal-fechar">&times;</span>
        <div id="modal-body-detalhes">Carregando...</div>
    </div>
</div>

<script src="/js/trilho_motoboy.js"></script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
