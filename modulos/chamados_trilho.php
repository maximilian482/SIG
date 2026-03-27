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

$cpf          = $_SESSION['cpf'];
$funcId       = $_SESSION['funcionario_id'];
$lojaUsuario  = $_SESSION['loja'] ?? 0;

// ===============================
// PERMISSÕES DO TRILHO
// ===============================
$isMotoboy = temAcesso($conn, $cpf, 'trilho_motoboy');

// Funções de permissão por chamado
function isSolicitante(array $c, int $funcId): bool {
    return intval($c['remetente_id']) === $funcId;
}

function isLojaSolicitada(array $c, int $lojaUsuario): bool {
    return intval($c['loja_destino_id']) === $lojaUsuario;
}

function isMotoboyDoPedido(array $c, int $funcId): bool {
    return !empty($c['motoboy_id']) && intval($c['motoboy_id']) === $funcId;
}

// ===============================
// CONTADORES POR STATUS
// ===============================
$sqlCont = "
    SELECT 
        status,
        COUNT(*) AS total
    FROM chamados_trilho
    WHERE 
        (status = 'entregue' AND DATE(assinatura_data) = CURDATE())
        OR status IN ('aberto', 'faturado', 'em rota')
    GROUP BY status
";


$resCont = $conn->query($sqlCont);

$cont = [
    'aberto'    => 0,
    'faturado'  => 0,
    'em_rota'   => 0,
    'entregue'  => 0
];

while ($row = $resCont->fetch_assoc()) {
    $status = strtolower($row['status']);
    if (isset($cont[$status])) {
        $cont[$status] = intval($row['total']);
    }
}

ob_start();
include ROOT_PATH . '/includes/flash.php';
?>

<link rel="stylesheet" href="/css/chamados_trilho.css">

<div class="trilho-acoes-topo">
    <a href="/modulos/chamados_publico.php" class="btn-voltar">⬅ Voltar</a>
    <a href="chamados_trilho_abrir.php" class="btn-novo">➕ Novo Protocolo</a>
    <a href="trilho_historico.php" class="btn-historico">📁 Histórico</a>
</div>

<h2>🚚 Trilho — Fluxo de Transferências</h2>

<div class="abas-trilho">
    <button class="aba ativa" data-aba="abertos">
        Abertos (<?= $cont['aberto'] ?>)
    </button>

    <button class="aba" data-aba="faturado">
        Faturado (<?= $cont['faturado'] ?>)
    </button>

    <button class="aba" data-aba="rota">
        Em rota (<?= $cont['em_rota'] ?>)
    </button>

    <button class="aba" data-aba="entregues">
        Entregues (<?= $cont['entregue'] ?>)
    </button>
</div>

<!-- ===============================
     ABERTOS — TODOS PODEM VER
=============================== -->
<div id="abertos" class="conteudo-aba ativo">
    <h3>📦 Abertos</h3>

    <?php
    $sqlAbertos = "
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
        WHERE LOWER(TRIM(ct.status)) = 'aberto'
        ORDER BY ct.id DESC
    ";

    $resAbertos = $conn->query($sqlAbertos);

    if ($resAbertos->num_rows == 0): ?>
        <p>Nenhum protocolo aberto.</p>
    <?php else: ?>

        <?php while ($c = $resAbertos->fetch_assoc()): ?>

            <?php
            $solicitante    = ($c['solicitante_id'] == $funcId);
            $solicitadoPara = ($c['solicitado_id'] == $funcId);
            ?>

            <div class="card-trilho">

                <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>

                <div class="card-header">
                    <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>
                    <span class="tag-status aberto">Aberto</span>
                </div>

                <div class="card-body">
                    <p><strong>Solicitante:</strong> <?= htmlspecialchars($c['nome_solicitante']) ?></p>
                    <p><strong>Origem:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
                    <p><strong>Solicitado para:</strong> <?= htmlspecialchars($c['nome_solicitado']) ?></p>
                    <p><strong>Loja Solicitada:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
                </div>

                <div class="card-actions">

                <!-- DETALHES (todos podem ver) -->
                <button class="btn-trilho btn-detalhes" data-id="<?= $c['id'] ?>">
                    Detalhes
                </button>

                <?php if ($solicitante): ?>
                    <!-- CRIADOR: pode editar e excluir -->
                    <a href="chamados_trilho_editar.php?id=<?= $c['id'] ?>" 
                    class="btn-trilho btn-editar">Editar</a>

                    <form action="chamados_trilho_excluir.php" method="POST"
                        onsubmit="return confirm('Tem certeza que deseja excluir este protocolo?')"
                        style="display:inline-block;">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button class="btn-trilho btn-excluir">Excluir</button>
                    </form>
                <?php endif; ?>

                <?php if (!$solicitante): ?>
                    <!-- SOLICITADO e OUTROS: podem faturar -->
                    <a href="chamados_trilho_faturar.php?id=<?= $c['id'] ?>" 
                    class="btn-trilho btn-faturar"
                    onclick="return confirm('Confirmar faturamento deste protocolo?')">
                    Faturar
                    </a>
                <?php endif; ?>

            </div>

            </div>

        <?php endwhile; ?>

    <?php endif; ?>
</div>


<!-- ===============================
     FATURADO
=============================== -->
<div id="faturado" class="conteudo-aba">
    <h3>📄 Faturado</h3>

    <?php
    $sqlFat = "
        SELECT 
            ct.*,
            lo.nome AS origem_nome,
            ld.nome AS destino_nome
        FROM chamados_trilho ct
        LEFT JOIN lojas lo ON lo.id = ct.loja_origem_id
        LEFT JOIN lojas ld ON ld.id = ct.loja_destino_id
        WHERE ct.status = 'faturado'
        ORDER BY ct.id DESC
    ";

    $resFat = $conn->query($sqlFat);

    if ($resFat->num_rows == 0): ?>
        <p>Nenhuma transferência faturada.</p>
    <?php else: ?>

        <?php while ($c = $resFat->fetch_assoc()): ?>

            <div class="card-trilho">

                <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>

                <div class="card-header">
                    <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>
                    <span class="tag-status faturado">Faturado</span>
                </div>

                <div class="card-body">
                    <p><strong>Origem:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
                    <p><strong>Destino:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
                </div>

                <div class="card-actions">

                    <!-- Detalhes (todos podem ver) -->
                    <button class="btn-trilho btn-detalhes" data-id="<?= $c['id'] ?>">
                        Detalhes
                    </button>

                    <!-- Coletar (somente motoboy com permissão trilho_motoboy) -->
                   <?php if ($isMotoboy && !in_array(strtolower($_SESSION['cargo']), ['super', 'ceo'])): ?>
                        <a href="chamados_trilho_coletar.php?id=<?= $c['id'] ?>" 
                        class="btn-trilho btn-coletar">
                        Coletar
                        </a>
                    <?php endif; ?>

                </div>

            </div>

        <?php endwhile; ?>

    <?php endif; ?>
</div>



<!-- ===============================
     EM ROTA
=============================== -->
<div id="rota" class="conteudo-aba">
    <h3>🛵 Em Rota</h3>

    <?php
    $sqlRota = "
        SELECT 
            ct.*,
            lo.nome AS origem_nome,
            ld.nome AS destino_nome
        FROM chamados_trilho ct
        LEFT JOIN lojas lo ON lo.id = ct.loja_origem_id
        LEFT JOIN lojas ld ON ld.id = ct.loja_destino_id
        WHERE ct.status = 'em_rota'
        ORDER BY ct.id DESC
    ";

    $resRota = $conn->query($sqlRota);

    if ($resRota->num_rows == 0): ?>
        <p>Nenhuma transferência em rota.</p>
    <?php else: ?>

        <?php while ($c = $resRota->fetch_assoc()): ?>

            <?php $motoboyDoPedido = ($c['motoboy_id'] == $funcId); ?>

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

                    <button class="btn-trilho btn-detalhes" data-id="<?= $c['id'] ?>">Detalhes</button>

                    <?php if ($motoboyDoPedido): ?>
                        <a href="chamados_trilho_entregar.php?id=<?= $c['id'] ?>" 
                           class="btn-trilho btn-entregar">Finalizar</a>
                    <?php endif; ?>

                </div>

            </div>

        <?php endwhile; ?>

    <?php endif; ?>
</div>


<!-- ===============================
     ENTREGUES — SOMENTE DO DIA
=============================== -->
<div id="entregues" class="conteudo-aba">
    <h3>📦 Entregues</h3>

    <?php
    $sqlEnt = "
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

    $resEnt = $conn->query($sqlEnt);

    if ($resEnt->num_rows == 0): ?>
        <p>Nenhuma entrega finalizada hoje.</p>
    <?php else: ?>

        <?php while ($c = $resEnt->fetch_assoc()): ?>

            <div class="card-trilho">

                <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>

                <div class="card-header">
                    <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>
                    <span class="tag-status entregues">Entregue</span>
                </div>

                <div class="card-body">
                    <p><strong>Origem:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
                    <p><strong>Destino:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
                </div>

                <div class="card-actions">
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
        <div id="modal-body-detalhes"></div>
    </div>
</div>


<script src="/js/chamados_trilho.js"></script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
