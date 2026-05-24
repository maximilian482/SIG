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
$isMotoboy = (
    temAcesso($conn, $cpf, 'trilho_motoboy')
    && strtolower($_SESSION['cargo']) === 'motoboy'
);



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
        OR status IN ('aberto', 'faturado', 'em_rota')
    GROUP BY status
";

$resCont = $conn->query($sqlCont);

$cont = [
    'abertos_total' => 0, // NOVO
    'aberto'        => 0,
    'faturado'      => 0,
    'em_rota'       => 0,
    'entregue'      => 0
];

while ($row = $resCont->fetch_assoc()) {
    $status = strtolower($row['status']);
    if (isset($cont[$status])) {
        $cont[$status] = intval($row['total']);
    }
}

// NOVO: soma de aberto + faturado
$cont['abertos_total'] = $cont['aberto'] + $cont['faturado'];


$mapaTitulos = [
    'documento'   => '📄 Documento',
    'medicamento' => '💊 Medicamentos',
    'malote'      => '📦 Malote',
    'item'        => '📦 Itens Diversos',
    'nota'        => '🧾 Notas',
    'comprovante' => '🧾 Comprovantes'
];


ob_start();
include ROOT_PATH . '/includes/flash.php';
?>

<link rel="stylesheet" href="/css/chamados_trilho.css">

<div class="trilho-acoes-topo">
    <a href="/modulos/chamados.php" class="btn-voltar">⬅ Voltar</a>
    <a href="chamados_trilho_abrir.php" class="btn-novo">➕ Novo Protocolo</a>
    <a href="trilho_historico.php" class="btn-historico">📁 Histórico</a>
</div>

<h2>🚚 Trilho — Fluxo de Transferências</h2>

<!-- ABAS -->
<div class="abas-trilho">
    <button class="aba ativa" data-aba="abertos">
        Abertos (<?= $cont['abertos_total'] ?>)
    </button>

    <button class="aba" data-aba="rota">
        Em rota (<?= $cont['em_rota'] ?>)
    </button>

    <button class="aba" data-aba="entregues">
        Entregues (<?= $cont['entregue'] ?>)
    </button>
</div>

    <!-- ===============================
        FILTROS POR ABA
    =============================== -->

    <div id="filtros-coletar" class="filtros-trilho" style="display:flex;">
        <label for="filtro-lib">Loja de Liberação:</label>
        <select id="filtro-lib">
            <option value="">Todas</option>
        </select>
        <button id="btn-limpar-coletar" class="btn-limpar-filtros">Limpar</button>
    </div>

    <div id="filtros-rota" class="filtros-trilho" style="display:none;">
        <label for="filtro-solic">Loja Solicitante:</label>
        <select id="filtro-solic">
            <option value="">Todas</option>
        </select>
        <button id="btn-limpar-rota" class="btn-limpar-filtros">Limpar</button>
    </div>

    <div id="filtros-entregues" class="filtros-trilho" style="display:none;">
        <label for="filtro-entregue">Loja Entregue:</label>
        <select id="filtro-entregue">
            <option value="">Todas</option>
        </select>
        <button id="btn-limpar-entregues" class="btn-limpar-filtros">Limpar</button>
    </div>


<!-- ===============================
     ABERTOS — MEDICAMENTOS + SIMPLES
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
        WHERE ct.status IN ('aberto', 'faturado')
        ORDER BY ct.id DESC
    ";

    $resAbertos = $conn->query($sqlAbertos);

    if ($resAbertos->num_rows == 0): ?>
        <p>Nenhum protocolo pendente.</p>
    <?php else: ?>

        <?php while ($c = $resAbertos->fetch_assoc()): ?>

            <?php
            $solicitante = ($c['solicitante_id'] == $funcId);
            $isTrilho = (!empty($_SESSION['trilho_motoboy']) && $_SESSION['trilho_motoboy'] == 1);
            ?>

            <div class="card-trilho">

                <h4 class="tipo-titulo"><?= $mapaTitulos[$c['tipo']] ?></h4><br>

                <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>

                <div class="card-header">
                    <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>

                    <?php if ($c['status'] == 'aberto'): ?>
                        <span class="tag-status aberto">Aberto</span>
                    <?php else: ?>
                        <span class="tag-status coletar">Liberado</span>
                    <?php endif; ?>
                </div>

                <div class="card-body">
                    <p><strong>Solicitante:</strong> <?= htmlspecialchars($c['nome_solicitante']) ?></p>
                    <p><strong>Solicitante:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
                    <p><strong>Aos cuidados de:</strong> <?= htmlspecialchars($c['nome_solicitado']) ?></p>
                    <p><strong>Liberação:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
                </div>

                <div class="card-actions">

                    <!-- Detalhes -->
                    <button type="button" 
                            class="btn-trilho btn-detalhes" 
                            data-id="<?= $c['id'] ?>">
                        Detalhes
                    </button>

                    <!-- MEDICAMENTO — ABERTO → FATURAR -->
                    <?php if ($c['tipo'] == 'medicamento' && $c['status'] == 'aberto' && !$solicitante): ?>
                        <button type="button"
                            class="btn-trilho btn-faturar"
                            data-id="<?= $c['id'] ?>">
                        Faturar
                    </button>

                    <?php endif; ?>

                    <!-- MEDICAMENTO — FATURADO → COLETAR -->
                    <?php if ($c['tipo'] == 'medicamento' && $c['status'] == 'faturado' && $isTrilho): ?>
                        <button type="button"
                                class="btn-trilho btn-coletar"
                                data-id="<?= $c['id'] ?>">
                            Coletar
                        </button>
                    <?php endif; ?>

                    <!-- SIMPLES — EDITAR/EXCLUIR -->
                    <?php if ($solicitante): ?>

                    <?php if ($c['tipo'] === 'medicamento'): ?>

                        <?php if ($c['status'] === 'aberto'): ?>
                            <a href="chamados_trilho_editar.php?id=<?= $c['id'] ?>" 
                            class="btn-trilho btn-editar">Editar</a>

                            <button type="button"
                                    class="btn-trilho btn-excluir"
                                    data-id="<?= $c['id'] ?>">
                                Excluir
                            </button>
                        <?php endif; ?>

                    <?php else: ?>

        <!-- SIMPLES: sempre pode editar/excluir -->
        <a href="chamados_trilho_editar_simples.php?id=<?= $c['id'] ?>" 
           class="btn-trilho btn-editar">Editar</a>

        <button type="button"
                class="btn-trilho btn-excluir"
                data-id="<?= $c['id'] ?>">
            Excluir
        </button>

    <?php endif; ?>

<?php endif; ?>



                    <!-- SIMPLES — COLETAR -->
                    <?php if ($c['tipo'] !== 'medicamento' && $isTrilho): ?>
                        <button type="button"
                                class="btn-trilho btn-coletar"
                                data-id="<?= $c['id'] ?>">
                            Coletar
                        </button>
                    <?php endif; ?>

                </div>

            </div>

        <?php endwhile; ?>

    <?php endif; ?>
</div>

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

                <h4 class="tipo-titulo"><?= $mapaTitulos[$c['tipo']] ?></h4><br>

                <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>

                <div class="card-header">
                    <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>
                    <span class="tag-status rota">Em rota</span>
                </div>

                <div class="card-body">
                    <p><strong>Saída:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
                    <p><strong>Entregue:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
                </div>

                <div class="card-actions">

                    <button type="button" 
                            class="btn-trilho btn-detalhes" 
                            data-id="<?= $c['id'] ?>">
                        Detalhes
                    </button>

                    <?php if ($motoboyDoPedido): ?>
                        <button type="button"
                                class="btn-trilho btn-entregar"
                                data-id="<?= $c['id'] ?>">
                            Finalizar
                        </button>
                    <?php endif; ?>

                </div>

            </div>

        <?php endwhile; ?>

    <?php endif; ?>
</div>

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

                <h4 class="tipo-titulo"><?= $mapaTitulos[$c['tipo']] ?></h4><br>

                <div class="card-produto"><?= htmlspecialchars($c['descricao']) ?></div>

                <div class="card-header">
                    <span class="protocolo"><?= htmlspecialchars($c['protocolo']) ?></span>
                    <span class="tag-status entregues">Entregue</span>
                </div>

                <div class="card-body">
                    <p><strong>Saída:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
                    <p><strong>Entregue:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
                </div>

                <div class="card-actions">
                    <button type="button" 
                            class="btn-trilho btn-detalhes" 
                            data-id="<?= $c['id'] ?>">
                        Detalhes
                    </button>
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

<!-- MODAL DE ESCOLHA DO TIPO -->
<div id="modalTipoProtocolo" class="modal-trilho">
    <div class="modal-conteudo tipo-protocolo">
        <span class="modal-fechar">&times;</span>

        <h3>Qual tipo de item você quer gerar o protocolo?</h3>

        <div class="opcoes-tipo-protocolo">
            <button class="btn-tipo" data-tipo="medicamento">💊 Medicamento</button>
            <button class="btn-tipo" data-tipo="documento">📄 Documento</button>
            <button class="btn-tipo" data-tipo="malote">📦 Malote</button>
            <button class="btn-tipo" data-tipo="item">📌 Item Diverso</button>
        </div>
    </div>
</div>

<script>
    // Abrir modal ao clicar em "Novo Protocolo"
document.querySelector('.btn-novo').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('modalTipoProtocolo').style.display = 'block';
});

// Fechar modal
document.querySelector('#modalTipoProtocolo .modal-fechar').onclick = function() {
    document.getElementById('modalTipoProtocolo').style.display = 'none';
};

// Clique fora fecha modal
window.onclick = function(e) {
    const modal = document.getElementById('modalTipoProtocolo');
    if (e.target === modal) modal.style.display = 'none';
};

// Redirecionamento por tipo
document.querySelectorAll('.btn-tipo').forEach(btn => {
    btn.addEventListener('click', function() {
        const tipo = this.dataset.tipo;

        if (tipo === 'medicamento') {
            window.location = "chamados_trilho_abrir.php?tipo=medicamento";
        } else {
            window.location = "chamados_trilho_abrir_simples.php?tipo=" + tipo;
        }
    });
});

</script>

<!-- MODAL DE FATURAMENTO -->
<div id="modalFaturar" class="modal-trilho" style="display:none;">
    <div class="modal-conteudo">
        <span class="modal-fechar" id="fecharModalFaturar">&times;</span>

        <h3>Faturar Protocolo</h3>

        <p>Informe o número da nota de transferência:</p>

        <input type="text" id="notaTransferencia" placeholder="Ex: 123456">

        <div class="modal-acoes">
            <button id="btnCancelarFaturar" class="btn-trilho btn-cancelar">Cancelar</button>
            <button id="btnConfirmarFaturar" class="btn-trilho btn-confirmar">Confirmar</button>
        </div>
    </div>
</div>


<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
