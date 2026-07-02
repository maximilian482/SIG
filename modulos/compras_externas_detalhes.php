<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

$cpf = $_SESSION['cpf'] ?? '';

if (!$cpf) {
    echo "<h2 class='text-center text-danger mt-4'>❌ Sessão expirada.</h2>";
    exit;
}

// Buscar dados do usuário
$stmt = $conn->prepare("SELECT id, nome FROM funcionarios WHERE cpf = ?");
$stmt->bind_param("s", $cpf);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

$usuarioId = $usuario['id'];

// ID da compra
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo "<h2 class='text-center text-danger mt-4'>❌ Solicitação inválida.</h2>";
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
    echo "<h2 class='text-center text-danger mt-4'>❌ Solicitação não encontrada.</h2>";
    exit;
}

// Regra de exclusão
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

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- CSS do módulo -->
<link rel="stylesheet" href="../css/compras_externas.css">

<div class="container py-4">

    <h2 class="mb-4">🛒 Detalhes da Solicitação #<?= $compra['id'] ?></h2>

    <!-- CARD DE INFORMAÇÕES -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <h4 class="mb-3">Informações da Solicitação</h4>

            <p><strong>Loja:</strong> <?= $compra['loja_nome'] ?></p>
            <p><strong>Solicitante:</strong> <?= nomeCurto($compra['solicitante_nome']) ?></p>
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
    </div>

    <?php if ($mostrarBotaoExcluir): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <button class="btn-excluir" onclick="excluirSolicitacao(<?= $id ?>)">
                🗑️ Excluir Solicitação
            </button>
        </div>
    </div>
    <?php endif; ?>

    <a href="compras_externas.php" class="btn-voltar">🔙 Voltar</a>

</div>

<script>
function excluirSolicitacao(id) {

    if (!confirm("Tem certeza que deseja excluir esta solicitação?")) return;

    fetch("../ajax/compras_externas_excluir.php", {
        method: "POST",
        body: JSON.stringify({ id })
    })
    .then(res => res.json())
    .then(ret => {

        if (ret.sucesso) {
            mostrarMensagem("Solicitação excluída com sucesso!", "sucesso");
            setTimeout(() => window.location.href = "compras_externas.php", 1500);
        } else {
            mostrarMensagem(ret.erro, "erro");
        }

    })
    .catch(() => {
        mostrarMensagem("Erro ao comunicar com o servidor.", "erro");
    });
}
</script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
