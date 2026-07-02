<?php
session_start();
require_once '../dados/conexao.php';
require_once '../includes/funcoes.php';

$conn = conectar();

// ===============================
// PERMISSÃO
// ===============================
$cpf   = $_SESSION['cpf'] ?? '';
$cargo = strtolower($_SESSION['cargo'] ?? '');

$acessoTotal = in_array($cargo, ['super', 'ceo']);

if (!$acessoTotal && !temAcesso($conn, $cpf, "gestao_compras_externas")) {
    echo "<h2 class='text-center text-danger mt-4'>❌ Você não tem permissão para acessar este módulo.</h2>";
    exit;
}

// ===============================
// ID DA COMPRA
// ===============================
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo "<h2 class='text-center text-danger mt-4'>❌ Solicitação inválida.</h2>";
    exit;
}

// ===============================
// BUSCAR DADOS DA COMPRA
// ===============================
$sql = "
    SELECT ce.*, 
           l.nome AS loja_nome,
           f.nome AS solicitante_nome
    FROM compras_externas ce
    JOIN lojas l ON l.id = ce.loja_id
    JOIN funcionarios f ON f.id = ce.solicitante_id
    WHERE ce.id = $id
";
$res = $conn->query($sql);
$compra = $res->fetch_assoc();

if (!$compra) {
    echo "<h2 class='text-center text-danger mt-4'>❌ Solicitação não encontrada.</h2>";
    exit;
}

// ===============================
// BUSCAR ANEXOS
// ===============================
$anexos = $conn->query("
    SELECT * FROM compras_externas_anexos 
    WHERE compra_id = $id
    ORDER BY id ASC
")->fetch_all(MYSQLI_ASSOC);

// ===============================
// FUNÇÃO NOME CURTO
// ===============================
function nomeCurto($nome) {
    $p = explode(" ", trim($nome));
    return $p[0] . " " . end($p);
}

ob_start();
?>

<link rel="stylesheet" href="../css/compras_externas.css">
<link rel="stylesheet" href="../css/compras_externas_detalhes.css">

<div class="container py-4">

    <h2 class="mb-3">📄 Detalhes da Solicitação #<?= $compra['id'] ?></h2>
    <p class="text-muted">
        Loja: <strong><?= htmlspecialchars($compra['loja_nome']) ?></strong> · 
        Solicitante: <strong><?= nomeCurto($compra['solicitante_nome']) ?></strong>
    </p>

    <!-- CARD PRINCIPAL -->
    <div class="card card-info mb-4">
        <h3>Informações Gerais</h3>

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
    <div class="card card-tipo mb-4">
        <h3>Tipo da Compra</h3>

        <p>
            <?= $compra['tipo_compra'] === 'com_nota' ? '📄 Compra com Nota Fiscal' : '🧾 Compra sem Nota (Ajuste)' ?>
        </p>
    </div>

    <!-- DADOS ESPECÍFICOS -->
    <div class="card card-form mb-4">
        <h3>Dados da Compra</h3>

        <?php if ($compra['tipo_compra'] === 'com_nota'): ?>

            <p><strong>Número da Nota:</strong> <?= htmlspecialchars($compra['numero_nota']) ?></p>
            <p><strong>Data da Compra:</strong> <?= htmlspecialchars($compra['data_compra']) ?></p>
            <p><strong>Valor:</strong> R$ <?= number_format($compra['valor'], 2, ',', '.') ?></p>
            <p><strong>Local da Compra:</strong> <?= htmlspecialchars($compra['local_compra']) ?></p>

        <?php else: ?>

            <p><strong>Data da Compra:</strong> <?= date('d/m/Y', strtotime($compra['data_compra'])) ?></p>
            <p><strong>Hora do Ajuste:</strong> <?= htmlspecialchars($compra['hora_ajuste']) ?></p>
            <p><strong>Quantidade Ajustada:</strong> <?= htmlspecialchars($compra['quantidade_ajustada']) ?></p>

        <?php endif; ?>

        <p><strong>Observações:</strong><br>
            <?= nl2br(htmlspecialchars($compra['observacoes'])) ?>
        </p>
    </div>

    <!-- ANEXOS -->
    <div class="card card-info mb-4">
    <h3>Anexos</h3>

    <?php if (empty($anexos)): ?>
        <p class="text-muted">Nenhum anexo enviado.</p>
    <?php else: ?>
        <ul class="lista-anexos">

            <?php foreach ($anexos as $a): ?>

                <?php
                    // Ícone por tipo
                    $icone = "📎";
                    if ($a['tipo'] === 'cupom') $icone = "🧾";
                    if ($a['tipo'] === 'print') $icone = "🖼️";
                    if ($a['tipo'] === 'nota')  $icone = "📄";

                    // Nome do usuário que enviou
                    $nomeUsuario = "Desconhecido";
                    if (!empty($a['enviado_por'])) {
                        $u = $conn->query("SELECT nome FROM funcionarios WHERE id = {$a['enviado_por']}")->fetch_assoc();
                        if ($u) $nomeUsuario = $u['nome'];
                    }
                ?>

                <li class="anexo-item">
                    <a href="/uploads/compras_externas/<?= $a['arquivo'] ?>" target="_blank">
                        <?= $icone ?> <?= htmlspecialchars($a['arquivo']) ?>
                    </a>

                    <div class="anexo-info">
                        <small>
                            Tipo: <strong><?= strtoupper($a['tipo']) ?></strong><br>
                            Enviado por: <strong><?= htmlspecialchars($nomeUsuario) ?></strong><br>
                            Data: <?= date("d/m/Y H:i", strtotime($a['criado_em'])) ?>
                        </small>
                    </div>
                </li>

            <?php endforeach; ?>

        </ul>
    <?php endif; ?>
</div>


    <!-- VOLTAR -->
    <a href="compras_externas_gestao.php" class="btn btn-secondary">🔙 Voltar</a>

</div>

<?php
$conteudo = ob_get_clean();
include '../includes/layout.php';
?>
