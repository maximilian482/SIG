<?php
ob_start();

session_start();

require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// ===============================
// VALIDAR LOJA
// ===============================
$lojaId = intval($_GET['id'] ?? 0);

if ($lojaId <= 0) {
    echo "<p>Loja inválida.</p>";
    exit;
}

// ===============================
// BUSCAR DADOS DA LOJA
// ===============================
$stmt = $conn->prepare("SELECT * FROM lojas WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$loja = $stmt->get_result()->fetch_assoc();

if (!$loja) {
    echo "<p>Loja não encontrada.</p>";
    exit;
}

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

// Função para status do certificado
function alertaCertificado($dataValidade) {
    if (!$dataValidade) return ['texto' => 'Não cadastrado', 'cor' => 'gray'];

    $hoje = new DateTime();
    $validade = new DateTime($dataValidade);
    $dias = $hoje->diff($validade)->days;

    if ($validade < $hoje)
        return ['texto' => "❌ Expirado há {$dias} dias", 'cor' => 'red'];

    if ($dias <= 30)
        return ['texto' => "⚠️ Vence em {$dias} dias", 'cor' => 'orange'];

    return ['texto' => "⏳ Vence em {$dias} dias", 'cor' => 'green'];
}

$alertaCert = alertaCertificado($certificado['validade'] ?? null);
$usuarioPodeVerSenha = true;

// ===============================
// FUNÇÕES AUXILIARES
// ===============================
function buscarFuncionarioPorId($conn, $id) {
    if (!$id) return ['nome' => 'Não informado', 'telefone' => ''];

    $stmt = $conn->prepare("
        SELECT nome, telefone 
        FROM funcionarios 
        WHERE id = ? AND desligamento IS NULL 
        LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    return $res->num_rows 
        ? $res->fetch_assoc() 
        : ['nome' => 'Não informado', 'telefone' => ''];
}

function campo($valor) {
    return ($valor && $valor !== "0") ? $valor : "Não informado";
}

// Montar endereço completo
$enderecoCompleto = trim(
    ($loja['endereco'] ?? '') . ', ' .
    ($loja['bairro'] ?? '') . ' - ' .
    ($loja['cidade'] ?? '') . '/' .
    ($loja['estado'] ?? '') . ' - ' .
    ($loja['cep'] ?? '')
);

// ===============================
// RESPONSÁVEIS
// ===============================
$gerente = buscarFuncionarioPorId($conn, intval($loja['gerente_id']));
$subger  = buscarFuncionarioPorId($conn, intval($loja['subgerente_id']));

// ===============================
// INDICADORES
// ===============================
$meta = number_format($loja['meta'], 2, ',', '.');

// Funcionários ativos
$funcAtivos = $conn->query("
    SELECT COUNT(*) AS total 
    FROM funcionarios 
    WHERE loja_id = {$lojaId} AND desligamento IS NULL
")->fetch_assoc()['total'];

// Chamados abertos
$chamadosAbertos = $conn->query("
    SELECT COUNT(*) AS total 
    FROM chamados 
    WHERE loja_origem = {$lojaId}
      AND LOWER(status) IN ('aberto','em andamento','reaberto','aguardando avaliação')
")->fetch_assoc()['total'];

// Pendências
$pendencias = $conn->query("
    SELECT COUNT(*) AS total 
    FROM chamados
    WHERE loja_destino = {$lojaId}
      AND LOWER(status) IN ('aberto','em andamento','reaberto','aguardando avaliação')
")->fetch_assoc()['total'];

?>

<link rel="stylesheet" href="/css/loja_painel.css">

<div class="topo-loja">

    <div class="topo-header">
        <a href="lojas.php" class="btn-voltar">⬅ Voltar</a>
        <h2>Painel da Unidade: <?= htmlspecialchars($loja['nome']) ?></h2>
    </div>

    <div class="indicadores-titulo">Indicadores da Unidade</div>

    <div class="indicadores-box">

        <div class="indicador">
            <span class="icone">📈</span>
            <span class="titulo">Meta da Loja</span>
            <span class="valor">R$ <?= $meta ?></span>
        </div>

        <div class="indicador">
            <span class="icone">👥</span>
            <span class="titulo">Funcionários ativos</span>
            <span class="valor"><?= $funcAtivos ?></span>
        </div>

        <div class="indicador">
            <span class="icone">🛠️</span>
            <span class="titulo">Chamados abertos</span>
            <span class="valor"><?= $chamadosAbertos ?></span>
        </div>

        <div class="indicador">
            <span class="icone">⚠️</span>
            <span class="titulo">Pendências</span>
            <span class="valor"><?= $pendencias ?></span>
        </div>

    </div>

</div>

<div class="aba-container">

<?php
// Define qual aba deve iniciar ativa
$abaAtiva = $_GET['aba'] ?? 'gerais';
?>

<!-- ===============================
     MENU INTERNO (ABAS)
=============================== -->
<div class="loja-menu">
    <button data-aba="gerais" class="<?= $abaAtiva === 'gerais' ? 'ativa' : '' ?>">Gerais</button>
    <button data-aba="certificado" class="<?= $abaAtiva === 'certificado' ? 'ativa' : '' ?>">Certificado Digital</button>
    <button data-aba="dispositivos" class="<?= $abaAtiva === 'dispositivos' ? 'ativa' : '' ?>">Dispositivos</button>
    <button data-aba="documentos" class="<?= $abaAtiva === 'documentos' ? 'ativa' : '' ?>">Documentos</button>
    <button data-aba="contratos" class="<?= $abaAtiva === 'contratos' ? 'ativa' : '' ?>">Contratos</button>
    <button data-aba="observacoes" class="<?= $abaAtiva === 'observacoes' ? 'ativa' : '' ?>">Observações</button>
</div>

<!-- ===============================
     CONTEÚDO DAS ABAS
=============================== -->

<div class="conteudo-aba <?= $abaAtiva === 'gerais' ? 'ativo' : '' ?>" id="gerais">
    <?php include __DIR__ . '/loja_gerais.php'; ?>
</div>

<div class="conteudo-aba <?= $abaAtiva === 'certificado' ? 'ativo' : '' ?>" id="certificado">
    <?php if (file_exists(__DIR__ . '/loja_certificado.php')) include __DIR__ . '/loja_certificado.php'; ?>
</div>

<div class="conteudo-aba <?= $abaAtiva === 'dispositivos' ? 'ativo' : '' ?>" id="dispositivos">
    <?php if (file_exists(__DIR__ . '/loja_dispositivos.php')) include __DIR__ . '/loja_dispositivos.php'; ?>
</div>

<div class="conteudo-aba <?= $abaAtiva === 'documentos' ? 'ativo' : '' ?>" id="documentos">
    <?php if (file_exists(__DIR__ . '/loja_documentos.php')) include __DIR__ . '/loja_documentos.php'; ?>
</div>

<div class="conteudo-aba <?= $abaAtiva === 'contratos' ? 'ativo' : '' ?>" id="contratos">
    <?php if (file_exists(__DIR__ . '/loja_contratos.php')) include __DIR__ . '/loja_contratos.php'; ?>
</div>

<div class="conteudo-aba <?= $abaAtiva === 'observacoes' ? 'ativo' : '' ?>" id="observacoes">
    <?php if (file_exists(__DIR__ . '/loja_observacoes.php')) include __DIR__ . '/loja_observacoes.php'; ?>
</div>

</div>


<script src="/js/loja.js"></script>
<script src="/js/global.js"></script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . "/includes/layout.php";
?>
<!-- ===============================
     MODAL PARA EDITAR CERTIFICADO
=============================== -->
<div id="modalCertificado" class="plano-modal hidden">
    <div class="plano-modal-conteudo">

        <button type="button" class="plano-modal-close modal-fechar-x">✖</button>

        <h3>✏️ Editar Certificado</h3>

        <form method="post" action="loja_certificado_salvar.php" enctype="multipart/form-data">
            <input type="hidden" name="loja_id" value="<?= $lojaId ?>">

            <label>Validade:</label>
            <input type="date" name="validade" value="<?= $certificado['validade'] ?? '' ?>">

            <label>Senha:</label>
            <input type="password" name="senha" value="<?= $certificado['senha'] ?? '' ?>">

            <label>Novo arquivo:</label>
            <input type="file" name="arquivo" accept=".pfx,.pdf,.crt,.pem">

            <div class="modal-botoes">
                <button class="btn-salvar">Salvar</button>
                <button type="button" class="btn-cancelar plano-modal-close">Cancelar</button>
            </div>
        </form>

    </div>
</div>

<script>

function abrirModalCertificado() {
    document.getElementById('modalCertificado').classList.remove('hidden');
}
</script>

<!-- ===============================
     MODAL PARA ADICIONAR / EDITAR DISPOSITIVO
=============================== -->
<div id="modalDispositivo" class="plano-modal hidden">
    <div class="plano-modal-conteudo">

        <button type="button" class="plano-modal-close modal-fechar-x">✖</button>

        <h3 id="tituloModalDispositivo">➕ Adicionar Dispositivo</h3>

        <form method="post" action="loja_dispositivo_salvar.php">

            <input type="hidden" name="loja_id" value="<?= $lojaId ?>">
            <input type="hidden" name="id" id="idDispositivo">

            <label>Nome:</label>
            <input type="text" name="nome" id="nomeDispositivo" required>

            <label>Localização:</label>
            <input type="text" name="localizacao" id="localizacaoDispositivo" required>

            <label>Descrição:</label>
            <textarea name="descricao" id="descricaoDispositivo" rows="4"></textarea>

            <div class="modal-botoes">
                <button class="btn-salvar">Salvar</button>
                <button type="button" class="btn-cancelar plano-modal-close">Cancelar</button>
            </div>

        </form>

    </div>
</div>

<script>
function abrirModalDispositivo() {
    document.getElementById('tituloModalDispositivo').innerText = "➕ Adicionar Dispositivo";
    document.getElementById('idDispositivo').value = "";
    document.getElementById('nomeDispositivo').value = "";
    document.getElementById('localizacaoDispositivo').value = "";
    document.getElementById('descricaoDispositivo').value = "";

    document.getElementById('modalDispositivo').classList.remove('hidden');
}

function editarDispositivo(id) {
    fetch("loja_dispositivo_get.php?id=" + id)
        .then(r => r.json())
        .then(d => {
            document.getElementById('tituloModalDispositivo').innerText = "✏️ Editar Dispositivo";
            document.getElementById('idDispositivo').value = d.id;
            document.getElementById('nomeDispositivo').value = d.nome;
            document.getElementById('localizacaoDispositivo').value = d.localizacao;
            document.getElementById('descricaoDispositivo').value = d.descricao;

            document.getElementById('modalDispositivo').classList.remove('hidden');
        });
}
</script>

