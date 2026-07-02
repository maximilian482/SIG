<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

$cpf = $_SESSION['cpf'] ?? '';
if (!$cpf) {
    echo "<h2 style='color:red; text-align:center;'>❌ Sessão expirada.</h2>";
    exit;
}

// Buscar usuário
$stmt = $conn->prepare("SELECT id, nome FROM funcionarios WHERE cpf = ?");
$stmt->bind_param("s", $cpf);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$usuarioId = $usuario['id'];

// ID da compra
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo "<h2 style='color:red; text-align:center;'>❌ Solicitação inválida.</h2>";
    exit;
}

// Buscar dados da compra
$sql = "
    SELECT ce.*, l.nome AS loja_nome, f.nome AS solicitante_nome
    FROM compras_externas ce
    JOIN lojas l ON l.id = ce.loja_id
    JOIN funcionarios f ON f.id = ce.solicitante_id
    WHERE ce.id = $id
";
$res = $conn->query($sql);
$compra = $res->fetch_assoc();

if (!$compra) {
    echo "<h2 style='color:red; text-align:center;'>❌ Solicitação não encontrada.</h2>";
    exit;
}

// Permissão para excluir
$usuarioPodeExcluir = (
    $usuarioId == $compra['solicitante_id'] ||
    temAcesso($conn, $cpf, 'super') ||
    temAcesso($conn, $cpf, 'ceo')
);

$mostrarBotaoExcluir = ($usuarioPodeExcluir && $compra['status'] == 'aberto');

function nomeCurto($nome) {
    $p = explode(" ", trim($nome));
    return $p[0] . " " . end($p);
}

ob_start();
?>

<link rel="stylesheet" href="../css/compras_externas.css">
<link rel="stylesheet" href="../css/compras_externas_detalhes.css">

<div class="detalhes-topo">
    <h2>🛒 Solicitação #<?= $compra['id'] ?></h2>
    <p class="detalhes-subtitulo">
        Loja: <strong><?= htmlspecialchars($compra['loja_nome']) ?></strong> ·
        Solicitante: <strong><?= nomeCurto($compra['solicitante_nome']) ?></strong>
    </p>
</div>

<!-- CARD PRINCIPAL -->
<div class="card card-info">
    <h3>Informações</h3>

    <p><strong>Produto:</strong> <?= htmlspecialchars($compra['produto']) ?></p>
    <p><strong>Quantidade:</strong> <?= $compra['quantidade'] ?></p>
    <p><strong>Motivo:</strong> <?= nl2br(htmlspecialchars($compra['motivo'])) ?></p>
    <p><strong>Urgência:</strong> <?= ucfirst($compra['urgencia']) ?></p>

    <p><strong>Status:</strong> 
        <span class="status status-<?= $compra['status'] ?>">
            <?= strtoupper($compra['status']) ?>
        </span>
    </p>
</div>

<!-- TIPO DA COMPRA -->
<div class="card card-tipo">
    <h3>Tipo da Compra</h3>

    <div class="tipo-opcoes">
        <button 
            class="btn-tipo <?= $compra['tipo_compra'] === 'com_nota' ? 'ativo' : '' ?>" 
            data-tipo="com_nota"
            onclick="alterarTipoCompra(<?= $compra['id'] ?>, 'com_nota')">
            📄 Com Nota
        </button>

        <button 
            class="btn-tipo <?= $compra['tipo_compra'] === 'sem_nota' ? 'ativo' : '' ?>" 
            data-tipo="sem_nota"
            onclick="alterarTipoCompra(<?= $compra['id'] ?>, 'sem_nota')">
            🧾 Sem Nota
        </button>
    </div>
</div>

<!-- FORMULÁRIO UNIFICADO -->
<div class="card card-form">
    <h3>Preencha os Dados</h3>

    <?php if ($compra['tipo_compra'] === 'com_nota'): ?>

        <div class="form-linha">
            <label>Número da Nota</label>
            <input type="text" id="numero_nota" value="<?= htmlspecialchars($compra['numero_nota'] ?? '') ?>">
        </div>

        <div class="form-linha">
            <label>Data da Compra</label>
            <input type="date" id="data_compra" value="<?= htmlspecialchars($compra['data_compra'] ?? '') ?>">
        </div>

        <div class="form-linha">
            <label>Valor</label>
            <input type="text" id="valor" value="<?= htmlspecialchars($compra['valor'] ?? '') ?>">
        </div>

        <div class="form-linha">
            <label>Local da Compra</label>
            <input type="text" id="local_compra" value="<?= htmlspecialchars($compra['local_compra'] ?? '') ?>">
        </div>

    <?php else: ?>

        <div class="form-linha">
            <label>Data da Compra</label>
            <input type="date" id="data_compra" value="<?= htmlspecialchars($compra['data_compra'] ?? '') ?>">
        </div>

        <div class="form-linha">
            <label>Hora do Ajuste</label>
            <input type="time" id="hora_ajuste" value="<?= htmlspecialchars($compra['hora_ajuste'] ?? '') ?>">
        </div>

        <div class="form-linha">
            <label>Quantidade Ajustada</label>
            <input type="number" id="quantidade_ajustada" value="<?= htmlspecialchars($compra['quantidade_ajustada'] ?? '') ?>">
        </div>

    <?php endif; ?>

   <?php if ($compra['tipo_compra'] === 'com_nota'): ?>

    <div class="form-linha">
        <label id="label_anexo">Cupom / Nota Fiscal</label>
        <input type="file" id="arquivo" multiple>
    </div>

<?php else: ?>

    <div class="form-linha">
        <label>Cupom</label>
        <input type="file" id="arquivo_cupom">
    </div>

    <div class="form-linha">
        <label>Print do Ajuste</label>
        <input type="file" id="arquivo_print">
    </div>

<?php endif; ?>

    <div class="form-linha">
        <label>Observações</label>
        <textarea id="observacoes"><?= htmlspecialchars($compra['observacoes'] ?? '') ?></textarea>
    </div>
</div>

<!-- AÇÕES -->
<div class="card card-acoes">

    <?php if ($compra['status'] !== 'concluido'): ?>
        <button class="btn-finalizar" onclick="finalizarCompra(<?= $compra['id'] ?>)">
            ✅ Finalizar Compra
        </button>
    <?php endif; ?>

    <?php if ($mostrarBotaoExcluir): ?>
        <button class="btn-excluir" onclick="excluirSolicitacao(<?= $compra['id'] ?>)">
            🗑️ Excluir
        </button>
    <?php endif; ?>

    <a href="compras_externas_gestao.php" class="btn-voltar">🔙 Voltar</a>
</div>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>

<!-- JS externo -->
<script src="/js/compras_externas.js"></script>
