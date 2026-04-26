<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// ===============================
// VARIÁVEIS DE SESSÃO
// ===============================
$cpf          = $_SESSION['cpf'];
$funcId       = $_SESSION['funcionario_id'];
$lojaUsuario  = $_SESSION['loja'] ?? 0;

// Só é motoboy se for cargo TRILHO (ou o nome que você usa)
$cargo = isset($_SESSION['cargo']) ? strtolower($_SESSION['cargo']) : '';

$isMotoboy = (
    temAcesso($conn, $cpf, 'trilho_motoboy')
    && $cargo === 'trilho'   // ajuste aqui se o nome do cargo for outro
);

// ===============================
// FUNÇÕES AUXILIARES (MESMAS DO CHAMADOS_TRILHO.PHP)
// ===============================
function isSolicitante(array $c, int $funcId): bool {
    return intval($c['solicitante_id']) === $funcId;
}

function isMotoboyDoPedido(array $c, int $funcId): bool {
    return !empty($c['motoboy_id']) && intval($c['motoboy_id']) === $funcId;
}

// ===============================
// FILTRO
// ===============================
$lib = $_GET['lib'] ?? '';

// ===============================
// SQL
// ===============================
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
    WHERE ct.status IN ('aberto', 'faturado')
";

if ($lib !== '') {
    $sql .= " AND ct.loja_destino_id = " . intval($lib);
}

$sql .= " ORDER BY ct.id DESC";

$res = $conn->query($sql);

// ===============================
// HTML
// ===============================
if ($res->num_rows == 0) {
    echo "<p>Nenhum protocolo pendente.</p>";
    exit;
}

while ($c = $res->fetch_assoc()):
    $solicitante = isSolicitante($c, $funcId);
?>
    <div class='card-trilho'>

        <h4 class='tipo-titulo'><?= htmlspecialchars($c['tipo']) ?></h4><br>

        <div class='card-produto'><?= htmlspecialchars($c['descricao']) ?></div>

        <div class='card-header'>
            <span class='protocolo'><?= htmlspecialchars($c['protocolo']) ?></span>

            <?php if ($c['status'] == 'aberto'): ?>
                <span class='tag-status aberto'>Aberto</span>
            <?php else: ?>
                <span class='tag-status coletar'>Liberado</span>
            <?php endif; ?>
        </div>

        <div class='card-body'>
            <p><strong>Solicitante:</strong> <?= htmlspecialchars($c['nome_solicitante']) ?></p>
            <p><strong>Entregar:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
            <p><strong>Aos cuidados de:</strong> <?= htmlspecialchars($c['nome_solicitado']) ?></p>
            <p><strong>Liberação:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
        </div>

        <div class='card-actions'>

            <!-- Detalhes -->
            <button class='btn-trilho btn-detalhes' data-id='<?= $c['id'] ?>'>Detalhes</button>

            <!-- MEDICAMENTO — ABERTO → FATURAR -->
            <?php if ($c['tipo'] == 'medicamento' && $c['status'] == 'aberto' && !$solicitante): ?>
                <button class='btn-trilho btn-faturar' data-id='<?= $c['id'] ?>'>Faturar</button>
            <?php endif; ?>

            <!-- MEDICAMENTO — FATURADO → COLETAR (somente motoboy) -->
            <?php if ($c['tipo'] == 'medicamento' && $c['status'] == 'faturado' && $isMotoboy): ?>
                <button class='btn-trilho btn-coletar' data-id='<?= $c['id'] ?>'>Coletar</button>
            <?php endif; ?>

            <!-- SIMPLES — EDITAR/EXCLUIR -->
            <?php if ($solicitante): ?>

                <?php if ($c['tipo'] === 'medicamento'): ?>

                    <?php if ($c['status'] === 'aberto'): ?>
                        <a href='/chamados_trilho_editar.php?id=<?= $c['id'] ?>' class='btn-trilho btn-editar'>Editar</a>
                        <button class='btn-trilho btn-excluir' data-id='<?= $c['id'] ?>'>Excluir</button>
                    <?php endif; ?>

                <?php else: ?>

                    <!-- SIMPLES: sempre pode editar/excluir -->
                    <a href='/chamados_trilho_editar_simples.php?id=<?= $c['id'] ?>' class='btn-trilho btn-editar'>Editar</a>
                    <button class='btn-trilho btn-excluir' data-id='<?= $c['id'] ?>'>Excluir</button>

                <?php endif; ?>

            <?php endif; ?>

            <!-- SIMPLES — COLETAR (somente motoboy) -->
            <?php if ($c['tipo'] !== 'medicamento' && $isMotoboy): ?>
                <button class='btn-trilho btn-coletar' data-id='<?= $c['id'] ?>'>Coletar</button>
            <?php endif; ?>

        </div>

    </div>

<?php endwhile; ?>
