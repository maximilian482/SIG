<?php
session_start();

require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

if (!isset($_SESSION['cpf'])) {
    header("Location: /login.php");
    exit;
}

$cpf    = $_SESSION['cpf'];
$funcId = $_SESSION['funcionario_id'] ?? 0;

if (!temAcesso($conn, $cpf, 'trilho_motoboy')) {
    $conteudo = "<h2 style='color:red; text-align:center; margin-top:40px;'>❌ Você não tem permissão para acessar o Trilho.</h2>";
    include ROOT_PATH . '/includes/layout.php';
    exit;
}

$mapaTitulos = [
    'medicamento'    => '💊 Medicamento',
    'perfumaria'     => '🧴 Perfumaria',
    'remanejamento'  => '📄 Remanejamento',
    'malote'         => '📦 Malote',
    'item'           => '📌 Item',
    'nota'           => '🧾 Notas',
    'comprovante'    => '🧾 Comprovantes',
    'documento'      => '📄 Documento'
];

ob_start();
?>

<link rel="stylesheet" href="/css/trilho_motoboy.css">

<div class="trilho-acoes-topo">
    <a href="/modulos/chamados_publico.php" class="btn-voltar">⬅ Voltar</a>
    <a href="trilho_motoboy_historico.php" class="btn-historico">📁 Histórico</a>
</div>

<h2 class="titulo-trilho">🚚 Trilho — Motoboy</h2>

<div class="abas-trilho">
    <button class="aba ativa" data-aba="coletar">Coletar</button>
    <button class="aba" data-aba="rota">Em rota</button>
    <button class="aba" data-aba="entregues">Entregues</button>
</div>

<div id="filtros-coletar" class="filtros-trilho">
    <select id="filtro-lib">
        <option value="">Loja de Liberação (todas)</option>
    </select>
    <button id="btn-limpar-coletar" class="btn-limpar-filtros">Limpar</button>
</div>

<div id="filtros-rota" class="filtros-trilho" style="display:none;">
    <select id="filtro-solic">
        <option value="">Loja Solicitante (todas)</option>
    </select>
    <button id="btn-limpar-rota" class="btn-limpar-filtros">Limpar</button>
</div>

<div id="filtros-entregues" class="filtros-trilho" style="display:none;">
    <select id="filtro-entregue">
        <option value="">Loja Entregue (todas)</option>
    </select>
    <button id="btn-limpar-entregues" class="btn-limpar-filtros">Limpar</button>
</div>

<!-- COLETAR -->
<div id="coletar" class="conteudo-aba ativo">
    <h3>📦 Transferências para Coletar</h3>

<?php
$sql = "
    SELECT 
        ct.*,
        lo.nome AS origem_nome,
        ld.nome AS destino_nome,
        fs.nome AS nome_solicitante,
        fd.nome AS nome_solicitado
    FROM chamados_trilho ct
    LEFT JOIN lojas lo ON lo.id = ct.loja_origem_id
    LEFT JOIN lojas ld ON ld.id = ct.loja_destino_id
    LEFT JOIN funcionarios fs ON fs.id = ct.solicitante_id
    LEFT JOIN funcionarios fd ON fd.id = ct.solicitado_id
    WHERE ct.status IN ('aberto','faturado')
    ORDER BY ct.id DESC
";

$res = $conn->query($sql);

if ($res->num_rows == 0):
    echo "<p>Nenhuma transferência pendente.</p>";
else:
    while ($c = $res->fetch_assoc()):
        $tipo = normalizarTipo($c['tipo']);
        $tipoSimples = in_array($tipo, ['remanejamento','malote','item']);
?>

<?php if ($tipoSimples): ?>

    <!-- CARD NOVO — TIPOS SIMPLES -->
    <div class="card-trilho card-simples">

        <h4 class="tipo-titulo tipo-<?= $tipo ?>">
            <?= $mapaTitulos[$tipo] ?? htmlspecialchars(ucfirst($tipo)) ?>
        </h4>

        <p class="tag-acao <?= $c['acao'] ?>">
            <?= $c['acao'] === 'enviar' ? '📤 Envio' : '📥 Recebimento' ?>
        </p>

        <div class="card-produto">
            <?= htmlspecialchars($c['descricao']) ?>
        </div>

        <div class="card-body">
            <p><strong>Origem:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
            <p><strong>Destino:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>

            <?php if ($c['acao'] === 'enviar'): ?>
                <p><strong>Aos cuidados de:</strong> <?= htmlspecialchars($c['nome_solicitado'] ?? '-') ?></p>
            <?php endif; ?>
        </div>

        <div class="card-actions">
            <button class="btn-trilho btn-detalhes-simples" data-id="<?= $c['id'] ?>">Detalhes</button>
            <!-- Motoboy: sempre pode coletar na aba ABERTOS para tipos simples -->
            <button class="btn-trilho btn-coletar" data-id="<?= $c['id'] ?>">Coletar</button>
        </div>

    </div>

<?php else: ?>

    <!-- CARD ANTIGO — TIPOS NÃO SIMPLES -->
    <div class="card-trilho">

        <h4 class="tipo-titulo tipo-<?= htmlspecialchars($tipo) ?>">
            <?= $mapaTitulos[$tipo] ?? htmlspecialchars(ucfirst($tipo)) ?>
        </h4>
        <br>

        <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>

        <div class="card-header">
            <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>

            <?php if ($c['status'] == 'aberto'): ?>
                <span class="tag-status aberto">Aberto</span>
            <?php elseif ($c['status'] == 'faturado'): ?>
                <span class="tag-status coletar">Liberado</span>
            <?php else: ?>
                <span class="tag-status"><?= htmlspecialchars($c['status']) ?></span>
            <?php endif; ?>
        </div>

        <div class="card-body">

            <?php if ($c['acao'] === 'enviar'): ?>

                <p><strong>Entregar:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
                <p><strong>Liberação:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
                <p><strong>Aos cuidados de:</strong> <?= htmlspecialchars($c['nome_solicitado'] ?? '-') ?></p>

            <?php else: ?>

                <p><strong>Enviado por:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
                <p><strong>Recebido por:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
                <p><strong>Responsável:</strong> <?= htmlspecialchars($c['nome_solicitado'] ?? '-') ?></p>

            <?php endif; ?>

        </div>

        <div class="card-actions">
            <button class="btn-trilho btn-detalhes" data-id="<?= $c['id'] ?>">Detalhes</button>

            <?php if ($c['status'] === 'faturado'): ?>
                <button class="btn-trilho btn-coletar" data-id="<?= $c['id'] ?>">Coletar</button>
            <?php endif; ?>
        </div>

    </div>

<?php endif; ?>

<?php endwhile; endif; ?>
</div>

<!-- ROTA -->
<div id="rota" class="conteudo-aba">
    <h3>🛵 Transferências em Rota</h3>

<?php
$sql2 = "
    SELECT 
        ct.*,
        lo.nome AS origem_nome,
        ld.nome AS destino_nome,
        fs.nome AS nome_solicitante,
        fd.nome AS nome_solicitado
    FROM chamados_trilho ct
    LEFT JOIN lojas lo ON lo.id = ct.loja_origem_id
    LEFT JOIN lojas ld ON ld.id = ct.loja_destino_id
    LEFT JOIN funcionarios fs ON fs.id = ct.solicitante_id
    LEFT JOIN funcionarios fd ON fd.id = ct.solicitado_id
    WHERE ct.status = 'em_rota'
    ORDER BY ct.id DESC
";

$res2 = $conn->query($sql2);

if ($res2->num_rows == 0):
    echo "<p>Nenhuma transferência em rota.</p>";
else:
    while ($c = $res2->fetch_assoc()):
        $tipo = normalizarTipo($c['tipo']);
        $tipoSimples = in_array($tipo, ['remanejamento','malote','item']);
?>

<?php if ($tipoSimples): ?>

    <div class="card-trilho card-simples">

        <h4 class="tipo-titulo tipo-<?= $tipo ?>">
            <?= $mapaTitulos[$tipo] ?? htmlspecialchars(ucfirst($tipo)) ?>
        </h4>

        <p class="tag-acao <?= $c['acao'] ?>">
            <?= $c['acao'] === 'enviar' ? '📤 Envio' : '📥 Recebimento' ?>
        </p>

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
            <button class="btn-trilho btn-detalhes-simples" data-id="<?= $c['id'] ?>">Detalhes</button>
            <a href="trilho_assinar.php?id=<?= $c['id'] ?>" class="btn-trilho btn-entregar">Entregar</a>
        </div>

    </div>

<?php else: ?>

    <div class="card-trilho">

        <h4 class="tipo-titulo tipo-<?= htmlspecialchars($tipo) ?>">
            <?= $mapaTitulos[$tipo] ?? htmlspecialchars(ucfirst($tipo)) ?>
        </h4>
        <br>

        <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>

        <div class="card-header">
            <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>
            <span class="tag-status rota">Em rota</span>
        </div>

        <div class="card-body">

            <?php if ($c['acao'] === 'enviar'): ?>

                <p><strong>Entregar:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
                <p><strong>Liberação:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
                <p><strong>Aos cuidados de:</strong> <?= htmlspecialchars($c['nome_solicitado'] ?? '-') ?></p>

            <?php else: ?>

                <p><strong>Enviado por:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
                <p><strong>Recebido por:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
                <p><strong>Responsável:</strong> <?= htmlspecialchars($c['nome_solicitado'] ?? '-') ?></p>

            <?php endif; ?>

        </div>

        <div class="card-actions">
            <button class="btn-trilho btn-detalhes" data-id="<?= $c['id'] ?>">Detalhes</button>
            <a href="trilho_assinar.php?id=<?= $c['id'] ?>" class="btn-trilho btn-entregar">Entregar</a>
        </div>

    </div>

<?php endif; ?>

<?php endwhile; endif; ?>
</div>

<!-- ENTREGUES -->
<div id="entregues" class="conteudo-aba">
    <h3>📄 Entregues (Hoje)</h3>

<?php
$sql3 = "
    SELECT 
        ct.*,
        lo.nome AS origem_nome,
        ld.nome AS destino_nome
    FROM chamados_trilho ct
    LEFT JOIN lojas lo ON lo.id = ct.loja_origem_id
    LEFT JOIN lojas ld ON ld.id = ct.loja_destino_id
    WHERE ct.status = 'entregue'
      AND DATE(ct.assinatura_data) = CURDATE()
    ORDER BY ct.id DESC
";

$res3 = $conn->query($sql3);

if ($res3->num_rows == 0):
    echo "<p>Nenhuma entrega finalizada hoje.</p>";
    echo '<p><a href="trilho_motoboy_historico.php" class="btn-historico">📚 Ver histórico completo</a></p>';
else:
    while ($c = $res3->fetch_assoc()):
        $tipo = normalizarTipo($c['tipo']);
        $tipoSimples = in_array($tipo, ['remanejamento','malote','item']);
?>

<?php if ($tipoSimples): ?>

    <div class="card-trilho card-simples entregue">

        <h4 class="tipo-titulo tipo-<?= $tipo ?>">
            <?= $mapaTitulos[$tipo] ?? htmlspecialchars(ucfirst($tipo)) ?>
        </h4>

        <p class="tag-acao <?= $c['acao'] ?>">
            <?= $c['acao'] === 'enviar' ? '📤 Envio' : '📥 Recebimento' ?>
        </p>

        <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>

        <div class="card-header">
            <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>
            <span class="tag-status entregue">Entregue</span>
        </div>

        <div class="card-body">
            <p><strong>Origem:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
            <p><strong>Destino:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
            <p><strong>Recebido por:</strong> <?= htmlspecialchars($c['assinatura_nome']) ?></p>
            <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($c['assinatura_data'])) ?></p>
        </div>

        <div class="card-actions">
            <button class="btn-trilho btn-detalhes-simples" data-id="<?= $c['id'] ?>">Detalhes</button>
        </div>

    </div>

<?php else: ?>

    <div class="card-trilho entregue">

        <h4 class="tipo-titulo tipo-<?= htmlspecialchars($tipo) ?>">
            <?= $mapaTitulos[$tipo] ?? htmlspecialchars(ucfirst($tipo)) ?>
        </h4>

        <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>

        <div class="card-header">
            <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>
            <span class="tag-status entregue">Entregue</span>
        </div>

        <div class="card-body">
            <p><strong>Solicitante:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
            <p><strong>Loja de Liberação:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
            <p><strong>Recebido por:</strong> <?= htmlspecialchars($c['assinatura_nome']) ?></p>
            <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($c['assinatura_data'])) ?></p>
        </div>

        <div class="card-actions">
            <button class="btn-trilho btn-detalhes" data-id="<?= $c['id'] ?>">Detalhes</button>
        </div>

    </div>

<?php endif; ?>

<?php endwhile; endif; ?>
</div>

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
