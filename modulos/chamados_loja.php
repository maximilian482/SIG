<?php
session_start();
require_once '../dados/conexao.php';
require_once '../includes/funcoes.php';
require_once __DIR__ . '/../config/bootstrap.php';

$conn = conectar();

// ===============================
// VALIDAR ACESSO
// ===============================
$cargo  = strtolower($_SESSION['cargo'] ?? '');
$cpf    = $_SESSION['cpf'] ?? '';
$lojaId = intval($_SESSION['loja'] ?? 0);

$temAcesso = in_array($cargo, ['gerente', 'subgerente'])
             || temAcesso($conn, $cpf, 'acesso_painel_loja');

if (!$temAcesso) {
    $conteudo = "<h3>Acesso restrito à gerência ou responsável autorizado da unidade.</h3>";
    include ROOT_PATH . '/includes/layout.php';
    exit;
}

// ===============================
// FILTRO POR ID DO CHAMADO
// ===============================
$filtroId = trim($_GET['id'] ?? '');

// ===============================
// BUSCAR CHAMADOS DA LOJA
// ===============================
$sql = "
    SELECT ch.*, 
           l.nome AS loja_origem_nome,
           f.nome AS solicitante_nome
    FROM chamados ch
    LEFT JOIN lojas l ON ch.loja_origem = l.id
    LEFT JOIN funcionarios f ON ch.solicitante_id = f.id
    WHERE ch.loja_destino = {$lojaId}
      AND LOWER(ch.status) IN ('aberto','em andamento','reaberto','aguardando avaliacao')
";

if ($filtroId !== '') {
    $idEsc = $conn->real_escape_string($filtroId);
    $sql .= " AND ch.codigo_chamado LIKE '%{$idEsc}%'";
}

$sql .= " ORDER BY ch.data_abertura ASC";

$chamados = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// ===============================
// CONTEÚDO PRINCIPAL
// ===============================
ob_start();


?>

<h2>🏬 Chamados da Loja</h2>
<p>Gerencie os chamados destinados à sua unidade.</p>

<form method="GET" class="filtro-form">
    <label>Buscar por Nº do Chamado:</label>
    <input type="text" name="id" placeholder="CHM-2026..." value="<?= htmlspecialchars($filtroId) ?>">
    <button type="submit" class="btn">Buscar</button>
    <a href="chamados_loja.php" class="btn-secondary">Limpar</a>
</form>

<hr>

<h3>Chamados Abertos</h3>

<div class="cards-container">

<?php foreach ($chamados as $c): ?>

    <?php
        $statusNorm = normalizarStatus($c['status']);
        $classeStatus = 'status-' . str_replace(' ', '-', $statusNorm);
        $primeiroNome = explode(' ', trim($c['solicitante_nome']))[0] ?? '-';
        $motivoReabertura = $c['motivo_reabertura'] ?? '';
        $mostrarBotaoFechar = in_array($statusNorm, ['aberto', 'em andamento', 'reaberto']);
    ?>

    <div class="card" onclick="abrirDetalhesChamado(<?= $c['id'] ?>)">
        
        <h2 class="<?= $classeStatus ?>">
            <?= ucfirst($statusNorm) ?>
        </h2>

        <p>
            <strong>Código:</strong> <?= htmlspecialchars($c['codigo_chamado']) ?><br>
            <strong>Solicitante:</strong> <?= htmlspecialchars($primeiroNome) ?><br>
            <strong>Abertura:</strong> <?= date('d/m/Y H:i', strtotime($c['data_abertura'])) ?>
        </p>

        <?php if (!empty($motivoReabertura)): ?>
            <p style="background:#f3e8ff; padding:8px; border-left:3px solid #8b5cf6; border-radius:6px;">
                <strong>Reaberto:</strong><br>
                <?= nl2br(htmlspecialchars($motivoReabertura)) ?>
            </p>
        <?php endif; ?>

        <?php if ($mostrarBotaoFechar): ?>
            <a href="#" onclick="event.stopPropagation(); abrirModalFecharChamado(<?= $c['id'] ?>)">
                Fechar
            </a>
        <?php endif; ?>

    </div>

<?php endforeach; ?>

</div>

<?php if (empty($chamados)): ?>
    <p>Nenhum chamado aberto encontrado para esta loja.</p>
<?php endif; ?><br><br>
<hr>

<a class="btn" href="chamados_encerrados_loja.php">📁 Ver Encerrados</a>
<a class="btn" href="../index.php">🔙 Voltar ao painel</a>

<?php
$conteudo = ob_get_clean();

// ===============================
// MODAIS
// ===============================
ob_start();
?>

<div id="modalDetalhesChamado" class="modal">
    <div class="modal-conteudo">
        <span class="modal-close" onclick="fecharModalDetalhesChamado()">×</span>
        <div id="conteudoDetalhesChamado">Carregando...</div>
    </div>
</div>

<div id="modalFecharChamado" class="modal">
    <div class="modal-conteudo">
        <span class="modal-close" onclick="fecharModalFecharChamado()">×</span>
        <h3>Fechar chamado</h3>

        <form id="formFecharChamado" onsubmit="enviarFechamentoChamado(event)">
            <input type="hidden" id="fecharChamadoId">

            <label><strong>Solução aplicada:</strong></label>
            <textarea id="fecharChamadoSolucao" rows="4" required></textarea>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="fecharModalFecharChamado()">Cancelar</button>
                <button type="submit" class="btn">Confirmar</button>
            </div>
        </form>
    </div>
</div>

<?php
$modais = ob_get_clean();

// ===============================
// SCRIPTS ESPECÍFICOS DA PÁGINA
// ===============================
$scripts = '<script src="/js/chamados_loja.js"></script>';

include ROOT_PATH . '/includes/layout.php';

