<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/menu.php';
include ROOT_PATH . '/perfil/menu_perfil.php';

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

    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Selecionar Cargo</title>
        <link rel="stylesheet" href="../css/acessos.css">
    </head>
    <body>

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

    </body>
    </html>
    <?php
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
   LISTA OFICIAL DE MÓDULOS (GESTÃO / SETORES / LOJA)
============================================================ */

$modulosDisponiveis = [

    // ---------------------------
    // MÓDULOS DE GESTÃO
    // ---------------------------
    'gestao_painel_chamados' => '🛠️ Painel de Chamados',
    'gestao_relatorios'      => '📄 Relatórios',
    'gestao_funcionarios'    => '👥 Funcionários',
    'gestao_inventario'      => '📦 Inventário',
    'gestao_lojas'           => '🏬 Lojas',
    'gestao_acessos'         => '🔐 Gestão de Acessos',

    // ---------------------------
    // MÓDULOS DE SETOR (Pendências)
    // ---------------------------
    'setor_entregas'         => '🚚 Setor Entregas',
    'setor_vendas'           => '🛒 Setor Vendas',
    'setor_diretoria'        => '🏛️ Setor Diretoria',
    'setor_prevencao'        => '🛡️ Setor Prevenção e Perdas',
    'setor_regulatorio'      => '⚖️ Setor Regulatório',
    'setor_marketing'        => '📢 Setor Marketing',
    'setor_convenios'        => '🤝 Setor Convênios',
    'setor_contabilidade'    => '📊 Setor Contabilidade',
    'setor_escritorio'       => '🏢 Setor Escritório',

    // ---------------------------
    // PERMISSÕES ESPECIAIS
    // ---------------------------
    'acesso_painel_loja'     => '🏪 Acesso ao Painel da Loja'
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

    header("Location: editar_acessos_padrao.php?cargo=$cargoSelecionado&sucesso=1");
    exit;
}

?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Acessos Padrão por Cargo</title>
    <link rel="stylesheet" href="../css/acessos.css">
</head>
<body>

<h2>⚙️ Acessos Padrão — Cargo: <strong><?= htmlspecialchars($cargoSelecionado) ?></strong></h2>

<form method="POST">
    <table>

        <!-- ============================
             SEÇÃO: ACESSOS DE GESTÃO
        ============================= -->
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


        <!-- ============================
             SEÇÃO: ACESSOS A SETORES
        ============================= -->
        <tr><th colspan="2" class="secao-titulo">🧭 Acessos a Setores (Pendências)</th></tr>

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


        <!-- ============================
             SEÇÃO: PERMISSÕES ESPECIAIS
        ============================= -->
        <tr><th colspan="2" class="secao-titulo">⚙️ Permissões Especiais</th></tr>

        <?php foreach ($modulosDisponiveis as $modulo => $label): ?>
            <?php if ($modulo === 'acesso_painel_loja'): ?>
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

    <button type="submit" style="margin-top:10px;">💾 Salvar Padrão</button>
    <a class="btn" href="gerenciar_acessos.php" style="margin-left:10px;">🔙 Voltar</a>
</form>

<?php if (isset($_GET['sucesso'])): ?>
    <div class="alerta-sucesso">
        ✅ Acessos padrão atualizados com sucesso!
    </div>
<?php endif; ?>

</body>
</html>
