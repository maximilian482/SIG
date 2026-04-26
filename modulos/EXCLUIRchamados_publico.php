<?php
session_start();

require_once '../includes/funcoes.php';
$conn = conectar();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/bootstrap.php';

// ===============================
// VARIÁVEIS DO USUÁRIO
// ===============================
$usuario       = $_SESSION['usuario'];
$cpf           = $_SESSION['cpf'] ?? '';
$lojaUsuario   = intval($_SESSION['loja'] ?? 0);
$setorUsuario  = intval($_SESSION['setor'] ?? 0);
$nomeUsuario   = $_SESSION['nome'] ?? $usuario;
$cargo         = strtolower(trim($_SESSION['cargo'] ?? ''));
$usuarioId     = intval($_SESSION['funcionario_id'] ?? ($_SESSION['id_funcionario'] ?? 0));

// ===============================
// FILTRO SIMPLIFICADO — APENAS Nº DO CHAMADO
// ===============================
$filtroChamado = trim($_GET['chamado'] ?? '');

// ===============================
// SQL BASE
// ===============================
$sqlBase = "
    FROM chamados c
    LEFT JOIN lojas lo ON lo.id = c.loja_origem
    LEFT JOIN lojas ld ON ld.id = c.loja_destino
    LEFT JOIN funcionarios f ON f.id = c.solicitante_id
    WHERE LOWER(TRIM(c.status)) NOT LIKE 'encerrado%'
";

// ===============================
// REGRA DE VISUALIZAÇÃO
// ===============================
if ($lojaUsuario > 0) {
    $sqlBase .= " AND c.loja_origem = {$lojaUsuario}";
} elseif ($setorUsuario > 0) {
    $sqlBase .= " AND c.setor_origem = {$setorUsuario}";
} else {
    $sqlBase .= " AND c.solicitante_id = {$usuarioId}";
}

// ===============================
// FILTRO POR NÚMERO DO CHAMADO
// ===============================
if (!empty($filtroChamado)) {
    $buscaEsc = $conn->real_escape_string($filtroChamado);
    $sqlBase .= " AND c.codigo_chamado LIKE '%{$buscaEsc}%'";
}

// ===============================
// BUSCAR CHAMADOS
// ===============================
$sqlDados = "
    SELECT 
        c.*,
        lo.nome AS nome_loja_origem,
        ld.nome AS nome_loja_destino,
        f.nome AS solicitante_nome
    " . $sqlBase . "
    ORDER BY c.data_abertura ASC
";

$chamados = $conn->query($sqlDados)->fetch_all(MYSQLI_ASSOC);

// ===============================
// NOME DA LOJA DO USUÁRIO
// ===============================
$nomeLoja = '—';

if ($lojaUsuario > 0) {
    $stmt = $conn->prepare("SELECT nome FROM lojas WHERE id = ?");
    $stmt->bind_param("i", $lojaUsuario);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res && !empty($res['nome'])) {
        $nomeLoja = $res['nome'];
    }
}

// ===============================
// CONTEÚDO PRINCIPAL
// ===============================
ob_start();
?>
<link rel="stylesheet" href="/css/chamados_publico.css">
<h2>📋 Chamados — 
    <?= $lojaUsuario > 0 ? htmlspecialchars($nomeLoja) : "Setor: " . htmlspecialchars($setorUsuarioNome ?? "—") ?>
</h2>

<p>
    <?= $lojaUsuario > 0 
        ? "Acompanhe os chamados abertos pela sua loja." 
        : "Acompanhe os chamados do seu setor." ?>
</p>

<!-- ===============================
     FILTRO SIMPLIFICADO
=============================== -->
<form method="GET" class="filtro-form">

    <div class="filtro-item">
        <label>Nº do Chamado:</label>
        <input type="text" name="chamado" placeholder="CHM-2025..." 
               value="<?= htmlspecialchars($filtroChamado) ?>">
    </div>

    <div class="filtro-botoes">
        <button type="submit" class="btn">Buscar</button>
        <a href="chamados_publico.php" class="btn-secondary">Limpar</a>
    </div>

</form>

<h3>Chamados Abertos</h3>

<div class="tabela-container">
<table class="tabela">
    <thead>
        <tr>
            <th>Chamado</th>
            <th>Destino</th>
            <th>Solicitante</th>
            <th style="text-align:center;">Detalhes</th>
            <th style="text-align:center;">Status</th>
        </tr>
    </thead>

    <tbody>
    <?php if (empty($chamados)): ?>
        <tr>
            <td colspan="5" style="text-align:center;">Nenhum chamado encontrado.</td>
        </tr>

    <?php else: ?>
        <?php foreach ($chamados as $c): ?>

            <?php
                $statusNorm  = normalizarStatus($c['status'] ?? '');

                // 🔥 CORREÇÃO: status "reaberto"
                if ($statusNorm === 'aberto' && !empty($c['data_reabertura'])) {
                    $statusNorm = 'reaberto';
                }

                $classeStatus = 'status-' . str_replace(' ', '-', $statusNorm);

                $primeiroNome = $c['solicitante_nome']
                    ? explode(' ', trim($c['solicitante_nome']))[0]
                    : '-';

                if ($c['setor_destino']) {
                    $nomeSetorDestino = $conn->query("
                        SELECT nome FROM setores WHERE id = {$c['setor_destino']}
                    ")->fetch_assoc()['nome'] ?? 'Setor';
                    $destino = "Setor: " . htmlspecialchars($nomeSetorDestino);
                } else {
                    $destino = "Loja: " . htmlspecialchars($c['nome_loja_destino'] ?? '');
                }

                $podeAvaliar = (
                    $statusNorm === 'aguardando avaliacao' &&
                    intval($c['solicitante_id']) === $usuarioId
                );
            ?>

            <tr>
                <td><?= htmlspecialchars($c['codigo_chamado']) ?></td>
                <td><?= $destino ?></td>
                <td><?= htmlspecialchars($primeiroNome) ?></td>

                <td style="text-align:center;">
                    <button type="button" class="btn-detalhes" onclick="abrirModalDetalhesChamado(<?= $c['id'] ?>)">🔍</button>
                </td>

                <td style="text-align:center;">
                    <?php if ($podeAvaliar): ?>
                    <button type="button" class="btn-avaliar" onclick="abrirModalAvaliacaoChamado(<?= $c['id'] ?>)">
                            📋 Avaliar
                        </button>
                    <?php else: ?>
                        <span class="<?= $classeStatus ?>">
                            <?= ucfirst($statusNorm) ?>
                        </span>
                    <?php endif; ?>
                </td>
            </tr>

        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
</div>

<div class="botoes-acoes">
    <a class="btn" href="../index.php">🏠 Voltar</a>
    <a class="btn" href="chamados_abrir.php">➕ Novo Chamado</a>
    <a class="btn" href="chamados_encerrados.php">📁 Encerrados</a>
    <a class="btn" href="chamados_trilho.php" >🚚 Trilho</a>
</div>

<?php
$conteudo = ob_get_clean();

// ===============================
// MODAIS (SEPARADOS DO MAIN)
// ===============================
ob_start();
?>

<!-- MODAL AVALIAÇÃO -->
<div id="modalAvaliacaoChamado" class="modal">
    <div class="modal-conteudo" onclick="event.stopPropagation()">
        <span class="modal-close" onclick="fecharModalAvaliacaoChamado()">×</span>
        <h3>📋 Avaliar atendimento</h3>

        <form id="formAvaliacaoChamado" onsubmit="enviarAvaliacaoChamado(event)">
            <input type="hidden" id="modalAvaliacaoChamadoId" name="id">

            <label><strong>Você foi atendido?</strong></label>
            <select id="modalAvaliacaoChamadoTipo" name="avaliacao" required onchange="modalToggleJustificativa()">
                <option value="">Selecione</option>
                <option value="Sim">Sim</option>
                <option value="Não">Não</option>
            </select>

            <div id="modalAvaliacaoEstrelas" style="display:none; margin-top:10px;">
                <label><strong>Como você avalia o atendimento?</strong></label>
                <div class="modal-estrelas-container">
                    <span class="modal-estrela" data-valor="1">→ 1 </span>
                    <span class="modal-estrela" data-valor="2">→ 2 </span>
                    <span class="modal-estrela" data-valor="3">→ 3 </span>
                    <span class="modal-estrela" data-valor="4">→ 4 </span>
                    <span class="modal-estrela" data-valor="5">→ 5 </span>
                </div>
                <input type="hidden" id="modalAvaliacaoNotaEstrelas" name="nota_estrelas">
            </div><br>


            <div id="modalJustificativaContainer" style="display:none; margin-top:10px;">
                <label><strong>Descreva o motivo:</strong></label>
                <textarea id="modalAvaliacaoChamadoJustificativa" name="justificativa" rows="3"></textarea>
            </div><br><br>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="fecharModalAvaliacaoChamado()">Cancelar</button>
                <button type="submit" class="btn">Confirmar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DETALHES -->
<div id="modalDetalhesChamado" class="modal">
    <div class="modal-conteudo" onclick="event.stopPropagation()">
        <span class="modal-close" onclick="fecharModalDetalhesChamado()">×</span>
        <h3>🔍 Detalhes do chamado</h3>
        <div id="conteudoDetalhesChamado">Carregando...</div>
    </div>
</div>

<script>
function modalToggleJustificativa() {
    const tipo = document.getElementById("modalAvaliacaoChamadoTipo").value;

    const blocoEstrelas = document.getElementById("modalAvaliacaoEstrelas");
    const blocoJustificativa = document.getElementById("modalJustificativaContainer");

    if (tipo === "Sim") {
        blocoEstrelas.style.display = "block";
        blocoJustificativa.style.display = "none";
    } else if (tipo === "Não") {
        blocoEstrelas.style.display = "none";
        blocoJustificativa.style.display = "block";
        document.getElementById("modalAvaliacaoNotaEstrelas").value = "";
    } else {
        blocoEstrelas.style.display = "none";
        blocoJustificativa.style.display = "none";
    }
}
</script>
<script>
document.querySelectorAll(".modal-estrela").forEach(estrela => {
    estrela.addEventListener("click", function () {
        const valor = this.getAttribute("data-valor");
        document.getElementById("modalAvaliacaoNotaEstrelas").value = valor;

        // pintar as estrelas
        document.querySelectorAll(".modal-estrela").forEach(e => {
            e.style.color = (e.getAttribute("data-valor") <= valor) ? "#f1c40f" : "#ccc";
        });
    });
});
</script>

<script src="/js/chamados_publico.js"></script>
<?php
$modais = ob_get_clean();

include ROOT_PATH . "/includes/layout.php";
