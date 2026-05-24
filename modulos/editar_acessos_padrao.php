<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

$cpfFuncionarioAtual = $_SESSION['cpf'] ?? '';

if (empty($cpfFuncionarioAtual)) {
    echo "❌ CPF do funcionário não encontrado na sessão.";
    exit;
}

// Verifica permissão
if (!temAcesso($conn, $cpfFuncionarioAtual, 'gestao_acessos')) {
    echo "<h2 style='color:red; text-align:center; margin-top:40px;'>❌ Você não tem permissão para acessar esta área.</h2>";
    exit;
}

/* ============================================================
   SELEÇÃO DE CARGO
============================================================ */

$cargoSelecionado = $_GET['cargo'] ?? '';

if (empty($cargoSelecionado)) {

    // Carregar lista de cargos do banco (SEM CEO e SEM SUPER)
    $cargos = [];
    $res = $conn->query("
        SELECT nome_cargo 
        FROM cargos 
        WHERE LOWER(nome_cargo) NOT IN ('ceo', 'super')
        ORDER BY nome_cargo ASC
    ");

    while ($row = $res->fetch_assoc()) {
        $cargos[] = $row['nome_cargo'];
    }

    ob_start();
    ?>

    <link rel="stylesheet" href="../css/acessos.css">

    <h2>⚙️ Editar Acessos Padrão por Cargo</h2>
    <p>Selecione o cargo que deseja configurar:</p>

    <form method="GET">
        <select name="cargo" required>
            <option value="">Selecione...</option>
            <?php foreach ($cargos as $cargo): ?>
                <option value="<?= htmlspecialchars($cargo) ?>"><?= htmlspecialchars($cargo) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn">Continuar</button>
    </form>

    <a class="btn" href="gerenciar_acessos.php" style="margin-top:20px;">🔙 Voltar</a>

    <?php
    $conteudo = ob_get_clean();
    include ROOT_PATH . '/includes/layout.php';
    exit;
}

/* ============================================================
   NORMALIZAÇÃO DO CARGO
============================================================ */

function normalizarCargo($texto) {
    $texto = strtolower($texto);
    $texto = str_replace(
        ['á','à','ã','â','é','ê','í','ó','ô','õ','ú','ç',' '],
        ['a','a','a','a','e','e','i','o','o','o','u','c',''],
        $texto
    );
    return $texto;
}

$cargoKey = 'padrao:' . normalizarCargo($cargoSelecionado);

/* ============================================================
   LISTA OFICIAL DE MÓDULOS
============================================================ */

$modulosDisponiveis = [

    // MÓDULOS DE GESTÃO
    'gestao_painel_chamados' => '🛠️ Painel de Chamados',
    'gestao_relatorios'      => '📄 Relatórios',
    'gestao_funcionarios'    => '👥 Funcionários',
    'gestao_lojas'           => '🏬 Lojas',
    'gestao_acessos'         => '🔐 Gestão de Acessos',

    // MÓDULOS DE SETOR (Pendências)
    'setor_entregas'         => '🚚 Setor Entregas',
    'setor_vendas'           => '🛒 Setor Vendas',
    'setor_diretoria'        => '🏛️ Setor Diretoria',
    'setor_prevencao'        => '🛡️ Setor Prevenção e Perdas',
    'setor_regulatorio'      => '⚖️ Setor Regulatório',
    'setor_marketing'        => '📢 Setor Marketing',
    'setor_convenios'        => '🤝 Setor Convênios',
    'setor_contabilidade'    => '📊 Setor Contabilidade',
    'setor_escritorio'       => '🏢 Setor Escritório',

    // FERRAMENTAS
    'ferramentas_avaliacoes' => '🏪 Avaliação de Loja',
    'ferramentas_auditoria'  => '📝 Auditoria',
    'ferramentas_inventario' => '📦 Inventário (Ferramentas)',
    'ferramentas_controlados'=> '💊 Controlados',

    // PERMISSÕES ESPECIAIS
    'acesso_painel_loja'     => '🏪 Acesso ao Painel da Loja',
    'trilho_motoboy'         => '🛵 MotoBoy do Trilho'
];

/* ============================================================
   CARREGAR ACESSOS PADRÃO DO CARGO
============================================================ */

$acessosPadrao = array_fill_keys(array_keys($modulosDisponiveis), false);

$stmt = $conn->prepare("SELECT modulo, acesso FROM acessos_usuarios WHERE cpf = ?");
$stmt->bind_param("s", $cargoKey);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if (isset($acessosPadrao[$row['modulo']])) {
        $acessosPadrao[$row['modulo']] = intval($row['acesso']) === 1;
    }
}
$stmt->close();

/* ============================================================
   SALVAR ALTERAÇÕES
============================================================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Comparar antes de salvar
    $ativados = 0;
    $desativados = 0;

    foreach ($modulosDisponiveis as $modulo => $label) {
        $novo = isset($_POST['acesso_' . $modulo]) ? 1 : 0;
        $antigo = $acessosPadrao[$modulo] ? 1 : 0;

        if ($novo === 1 && $antigo === 0) $ativados++;
        if ($novo === 0 && $antigo === 1) $desativados++;
    }

    // Apagar acessos antigos
    $stmtDel = $conn->prepare("DELETE FROM acessos_usuarios WHERE cpf = ?");
    $stmtDel->bind_param("s", $cargoKey);
    $stmtDel->execute();

    // Inserir novos acessos
    $stmt = $conn->prepare("
        INSERT INTO acessos_usuarios (cpf, modulo, acesso)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE acesso = VALUES(acesso)
    ");

    foreach ($modulosDisponiveis as $modulo => $label) {
        $acesso = isset($_POST['acesso_' . $modulo]) ? 1 : 0;
        $stmt->bind_param("ssi", $cargoKey, $modulo, $acesso);
        $stmt->execute();
    }

    header("Location: editar_acessos_padrao.php?cargo=$cargoSelecionado&sucesso=1&ativados=$ativados&desativados=$desativados");
    exit;
}

/* ============================================================
   INÍCIO DO CONTEÚDO
============================================================ */

ob_start();
?>

<?php if (isset($_GET['sucesso'])): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {

    const ativados = <?= intval($_GET['ativados'] ?? 0) ?>;
    const desativados = <?= intval($_GET['desativados'] ?? 0) ?>;

    let msg = "";

    if (ativados === 0 && desativados === 0) {
        msg = "Nenhuma alteração realizada.";
        mostrarMensagem(msg, "alerta");
        return;
    }

    msg = "Acessos padrão atualizados!<br>";

    if (ativados > 0) msg += "🔓 " + ativados + " permissões ativadas<br>";
    if (desativados > 0) msg += "🔒 " + desativados + " permissões removidas";

    mostrarMensagem(msg, "sucesso");
});
</script>
<?php endif; ?>

<link rel="stylesheet" href="../css/acessos.css">

<h2>⚙️ Acessos Padrão — Cargo: <strong><?= htmlspecialchars($cargoSelecionado) ?></strong></h2>

<form method="POST">
    <table class="tabela-funcionarios">

        <!-- GESTÃO -->
        <tr><th colspan="2" class="secao-titulo">📊 Acessos de Gestão</th></tr>
        <?php foreach ($modulosDisponiveis as $modulo => $label): ?>
            <?php if (str_starts_with($modulo, 'gestao_')): ?>
                <tr>
                    <td><?= $label ?></td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" name="acesso_<?= $modulo ?>" <?= $acessosPadrao[$modulo] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- SETORES -->
        <tr><th colspan="2" class="secao-titulo">🧭 Acessos a Setores</th></tr>
        <?php foreach ($modulosDisponiveis as $modulo => $label): ?>
            <?php if (str_starts_with($modulo, 'setor_')): ?>
                <tr>
                    <td><?= $label ?></td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" name="acesso_<?= $modulo ?>" <?= $acessosPadrao[$modulo] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- FERRAMENTAS -->
        <tr><th colspan="2" class="secao-titulo">🧰 Ferramentas</th></tr>
        <?php foreach ($modulosDisponiveis as $modulo => $label): ?>
            <?php if (str_starts_with($modulo, 'ferramentas_')): ?>
                <tr>
                    <td><?= $label ?></td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" name="acesso_<?= $modulo ?>" <?= $acessosPadrao[$modulo] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- PERMISSÕES ESPECIAIS -->
        <tr><th colspan="2" class="secao-titulo">⚙️ Permissões Especiais</th></tr>
        <?php foreach ($modulosDisponiveis as $modulo => $label): ?>
            <?php if (in_array($modulo, ['acesso_painel_loja', 'trilho_motoboy'])): ?>
                <tr>
                    <td><?= $label ?></td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" name="acesso_<?= $modulo ?>" <?= $acessosPadrao[$modulo] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>

    </table>

    <button type="submit" class="btn" style="margin-top:15px;">💾 Salvar Padrão</button>
    <a class="btn" href="gerenciar_acessos.php" style="margin-left:10px;">🔙 Voltar</a>
</form>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
