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
$nomeUsuario   = $_SESSION['nome'] ?? $usuario;
$cargo         = strtolower(trim($_SESSION['cargo'] ?? ''));
$usuarioId     = intval($_SESSION['funcionario_id'] ?? ($_SESSION['id_funcionario'] ?? 0));

// ===============================
// FILTRO SIMPLIFICADO
// ===============================
$filtroChamado = trim($_GET['chamado'] ?? '');

// ===============================
// SQL BASE — APENAS CHAMADOS PARA SETORES
// ===============================
$sqlBase = "
    FROM chamados c
    LEFT JOIN setores s ON s.id = c.setor_destino
    LEFT JOIN funcionarios f ON f.id = c.solicitante_id
    WHERE c.setor_destino > 0
        AND c.loja_destino = 0
        AND c.solicitante_id = {$usuarioId}


";

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
        s.nome AS nome_setor_destino,
        f.nome AS solicitante_nome
    " . $sqlBase . "
    ORDER BY c.data_abertura DESC
";

$chamados = $conn->query($sqlDados)->fetch_all(MYSQLI_ASSOC);

// ===============================
// CONTEÚDO PRINCIPAL
// ===============================
ob_start();
?>

<link rel="stylesheet" href="/css/chamados_publico.css">

<h2>📋 Chamados — Minhas solicitações para Setores</h2>
<p>Acompanhe os chamados que você abriu para setores internos.</p>

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
        <a href="chamados_setores_publico.php" class="btn-secondary">Limpar</a>
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

                if ($statusNorm === 'aberto' && !empty($c['data_reabertura'])) {
                    $statusNorm = 'reaberto';
                }

                $classeStatus = 'status-' . str_replace(' ', '-', $statusNorm);

                $primeiroNome = $c['solicitante_nome']
                    ? explode(' ', trim($c['solicitante_nome']))[0]
                    : '-';

                $destino = "Setor: " . htmlspecialchars($c['nome_setor_destino'] ?? '—');

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
    <a class="btn" href="chamados.php">⬅ Voltar</a>
    <a class="btn" href="chamados_abrir_setores.php">➕ Novo Chamado</a>
</div>

<?php
$conteudo = ob_get_clean();

// ===============================
// MODAIS (MESMOS DO ORIGINAL)
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

<script src="/js/chamados_publico.js"></script>

<?php
$modais = ob_get_clean();

include ROOT_PATH . "/includes/layout.php";
?>
