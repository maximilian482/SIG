<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// ===============================
// CONFIGURAÇÕES DO LAYOUT
// ===============================
$titulo   = "Painel da Loja";
$cssExtra = "/css/loja.css";

// ===============================
// CAPTURA DO ID DA LOJA
// ===============================
$lojaId = intval($_GET['id'] ?? 0);

// ===============================
// FUNÇÕES AUXILIARES
// ===============================
function buscarFuncionarioPorId($conn, $id) {
    if (!$id) return ['nome' => '—', 'telefone' => ''];
    $stmt = $conn->prepare("SELECT nome, telefone FROM funcionarios WHERE id = ? AND desligamento IS NULL LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->num_rows ? $res->fetch_assoc() : ['nome' => '—', 'telefone' => ''];
}

function alertaCertificado($dataValidade) {
    if (!$dataValidade) return ['texto' => 'Não cadastrado', 'cor' => 'gray'];
    $hoje = new DateTime();
    $validade = new DateTime($dataValidade);
    $dias = $hoje->diff($validade)->days;

    if ($validade < $hoje)   return ['texto' => "❌ Expirado há {$dias} dias", 'cor' => 'red'];
    if ($dias <= 30)         return ['texto' => "⚠️ Vence em {$dias} dias",  'cor' => 'orange'];
    return ['texto' => "⏳ Vence em {$dias} dias", 'cor' => 'green'];
}

// ===============================
// BUSCAR LOJA
// ===============================
$stmt = $conn->prepare("SELECT * FROM lojas WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    $_SESSION['erros'] = ["Loja não encontrada."];
    header("Location: lojas.php");
    exit;
}

$loja = $res->fetch_assoc();

// ===============================
// CERTIFICADO DIGITAL
// ===============================
$stmtCert = $conn->prepare("
    SELECT validade, arquivo, TRIM(COALESCE(senha, '')) AS senha
    FROM lojas_certificados
    WHERE loja_id = ?
    LIMIT 1
");
$stmtCert->bind_param("i", $lojaId);
$stmtCert->execute();
$certificado = $stmtCert->get_result()->fetch_assoc();
$alertaCert  = alertaCertificado($certificado['validade'] ?? null);

// ===============================
// EQUIPAMENTOS
// ===============================
$equipamentos = [];
$stmt = $conn->prepare("SELECT nome, ip, observacao FROM lojas_equipamentos WHERE loja_id = ?");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$res = $stmt->get_result();
while ($eq = $res->fetch_assoc()) $equipamentos[] = $eq;

// ===============================
// FUNCIONÁRIOS ATIVOS
// ===============================
$stmt = $conn->prepare("SELECT nome FROM funcionarios WHERE loja_id = ? AND desligamento IS NULL");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$funcionariosAtivos = $stmt->get_result()->num_rows;

// ===============================
// CHAMADOS ABERTOS
// ===============================
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM chamados
    WHERE loja_origem = ?
      AND LOWER(status) IN ('aberto','em andamento','reaberto','aguardando avaliação')
");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$chamadosAbertos = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// ===============================
// PENDÊNCIAS
// ===============================
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM chamados
    WHERE loja_destino = ?
      AND LOWER(status) IN ('aberto','em andamento','reaberto','aguardando avaliação')
");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$pendenciasLoja = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// ===============================
// RESPONSÁVEIS
// ===============================
$gerente  = buscarFuncionarioPorId($conn, intval($loja['gerente_id']));
$subger   = buscarFuncionarioPorId($conn, intval($loja['subgerente_id']));

// ===============================
// META DA LOJA
// ===============================
$stmtMeta = $conn->prepare("SELECT meta FROM lojas WHERE id = ?");
$stmtMeta->bind_param("i", $lojaId);
$stmtMeta->execute();
$meta = $stmtMeta->get_result()->fetch_assoc()['meta'] ?? 0;

// Permissão
$permiteEditarMeta = in_array(strtolower($_SESSION['cargo'] ?? ''), ['gerente','subgerente','super','ceo']);

// ===============================
// INICIAR HTML
// ===============================
ob_start();
?>

<?php if (!empty($_SESSION['sucesso'])): ?>
<script>mostrarMensagem("<?= addslashes($_SESSION['sucesso']) ?>","sucesso");</script>
<?php unset($_SESSION['sucesso']); endif; ?>

<?php if (!empty($_SESSION['erros'])): ?>
<script>mostrarMensagem("<?= addslashes(implode(' | ', $_SESSION['erros'])) ?>","erro");</script>
<?php unset($_SESSION['erros']); endif; ?>

<h2>🏪 Painel da Unidade: <?= htmlspecialchars($loja['nome']) ?></h2>

<!-- ===============================
     INDICADORES
=============================== -->
<div class="secao">
    <h3>📊 Indicadores operacionais</h3>

    <div class="tabela-container">
        <table class="tabela">
            <tr><th>Indicador</th><th>Valor</th></tr>

            <tr>
                <td><strong>🎯 Meta da Loja</strong></td>
                <td>
                    R$ <?= number_format($meta, 2, ',', '.') ?>
                    <?php if ($permiteEditarMeta): ?>
                        <button class="btn-small" onclick="abrirModalMeta()">✏️</button>
                    <?php endif; ?>
                </td>
            </tr>

            <tr>
                <td>Funcionários ativos</td>
                <td><span class="badge verde"><?= $funcionariosAtivos ?></span></td>
            </tr>

            <tr>
                <td>Chamados abertos</td>
                <td>
                    <?= $chamadosAbertos > 0 
                        ? "<span class='badge amarelo'>{$chamadosAbertos}</span>"
                        : "<span class='badge verde'>Nenhum chamado</span>" ?>
                </td>
            </tr>

            <tr>
                <td>Pendências</td>
                <td>
                    <?= $pendenciasLoja > 0 
                        ? "<span class='badge amarelo'>{$pendenciasLoja}</span>"
                        : "<span class='badge verde'>Tudo certo</span>" ?>
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- ===============================
     INFORMAÇÕES GERAIS
=============================== -->
<div class="secao">
    <h3>📋 Informações gerais <a href="editar_info_gerais.php?id=<?= $lojaId ?>">✏️</a></h3>

    <div class="info-box"><strong>Nome:</strong> <?= htmlspecialchars($loja['nome']) ?></div>
    <div class="info-box"><strong>CNPJ:</strong> <?= htmlspecialchars($loja['cnpj']) ?></div>
    <div class="info-box"><strong>Inscrição Estadual:</strong> <?= htmlspecialchars($loja['inscricao_estadual']) ?></div>

    <div class="info-box">
        <strong>Responsável:</strong>
        <?= htmlspecialchars($gerente['nome']) ?>
        <?= $gerente['telefone'] ? "📞 " . htmlspecialchars($gerente['telefone']) : '' ?>
    </div>

    <div class="info-box">
        <strong>2º Responsável:</strong>
        <?= htmlspecialchars($subger['nome']) ?>
        <?= $subger['telefone'] ? "📞 " . htmlspecialchars($subger['telefone']) : '' ?>
    </div>

    <div class="info-box">
        <strong>Endereço:</strong>
        <?= htmlspecialchars($loja['endereco']) ?>,
        <?= htmlspecialchars($loja['bairro']) ?> -
        <?= htmlspecialchars($loja['cidade']) ?>/<?= htmlspecialchars($loja['estado']) ?>,
        <?= htmlspecialchars($loja['cep']) ?>
    </div>

    <div class="info-box"><strong>Telefone fixo:</strong> <?= htmlspecialchars($loja['telefone_fixo']) ?></div>
    <div class="info-box"><strong>Celular:</strong> <?= htmlspecialchars($loja['celular']) ?></div>
    <div class="info-box"><strong>Email (Gmail):</strong> <?= htmlspecialchars($loja['email_gmail']) ?></div>
    <div class="info-box"><strong>Email corporativo:</strong> <?= htmlspecialchars($loja['email_corporativo']) ?></div>
    <div class="info-box"><strong>Funcionamento:</strong> <?= htmlspecialchars($loja['dias_funcionamento']) ?></div>
    <div class="info-box"><strong>Observações:</strong> <?= nl2br(htmlspecialchars($loja['observacoes'] ?? '—')) ?></div>
</div>

<!-- ===============================
     CERTIFICADO DIGITAL
=============================== -->
<div class="secao">
    <h3>📑 Certificado Digital <a href="editar_certificado.php?id=<?= $lojaId ?>">✏️</a></h3>

    <div class="info-box"><strong>Validade:</strong>
        <?= !empty($certificado['validade']) ? date('d/m/Y', strtotime($certificado['validade'])) : '—' ?>
    </div>

    <div class="info-box" style="color: <?= $alertaCert['cor'] ?>;">
        <strong>Status:</strong> <?= $alertaCert['texto'] ?>
    </div>

    <div class="info-box">
        <strong>Arquivo:</strong>
        <?php if (!empty($certificado['arquivo'])): ?>
            <a href="../<?= htmlspecialchars($certificado['arquivo']) ?>" download>📥 Baixar certificado</a>
        <?php else: ?>
            — Nenhum arquivo definido
        <?php endif; ?>
    </div>

    <div class="info-box">
        <strong>Senha:</strong>
        <?php if (!empty($certificado['senha'])): ?>
            <input type="password" id="senhaCert" value="<?= htmlspecialchars($certificado['senha']) ?>" readonly>
            <button class="btn-small" onclick="toggleSenha()">👁️</button>
        <?php else: ?>
            — Nenhuma senha definida
        <?php endif; ?>
    </div>

    <script>
    function toggleSenha() {
        const campo = document.getElementById('senhaCert');
        campo.type = campo.type === "password" ? "text" : "password";
    }
    </script>
</div>

<!-- ===============================
     INVENTÁRIO
=============================== -->
<div class="secao">
    <h3>🧮 Inventário de Dispositivos <a href="editar_equipamentos.php?nome=<?= urlencode($loja['nome']) ?>">✏️</a></h3>

    <?php if (!empty($equipamentos)): ?>
        <div class="tabela-container">
            <table class="tabela">
                <tr><th>Nome</th><th>IP</th><th>Observação</th></tr>
                <?php foreach ($equipamentos as $eq): ?>
                <tr>
                    <td><?= htmlspecialchars($eq['nome']) ?></td>
                    <td><?= htmlspecialchars($eq['ip']) ?></td>
                    <td><?= htmlspecialchars($eq['observacao']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php else: ?>
        <p>Nenhum equipamento cadastrado.</p>
    <?php endif; ?>
</div>

<!-- ===============================
     BOTÕES FINAIS
=============================== -->
<div class="botoes-acoes">
    <a class="btn" href="lojas.php">🔙 Voltar</a>
</div>

<!-- ===============================
     MODAL META
=============================== -->
<div id="modalMeta" class="modal">
    <div class="modal-conteudo">
        <h3>Editar Meta da Loja</h3>

        <form method="POST" action="loja_salvar_meta.php">
            <input type="hidden" name="loja_id" value="<?= $lojaId ?>">

            <label>Nova Meta:</label>
            <input type="number" step="0.01" name="meta" value="<?= $meta ?>" required>

            <button class="btn">Salvar</button>
            <button type="button" class="btn-secondary" onclick="fecharModalMeta()">Cancelar</button>
        </form>
    </div>
</div>

<script>
function abrirModalMeta() {
    document.getElementById('modalMeta').style.display = 'flex';
}
function fecharModalMeta() {
    document.getElementById('modalMeta').style.display = 'none';
}
</script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . "/includes/layout.php";
