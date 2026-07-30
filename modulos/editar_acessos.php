<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

$cpfFuncionarioAtual = $_SESSION['cpf'] ?? '';

if (empty($cpfFuncionarioAtual)) {
    echo "<h2 style='color:red; text-align:center;'>❌ CPF do funcionário não encontrado na sessão.</h2>";
    exit;
}

// Verifica permissão do usuário que está editando acessos
if (!temAcesso($conn, $cpfFuncionarioAtual, 'gestao_acessos')) {
    echo "<h2 style='color:red; text-align:center; margin-top:40px;'>❌ Você não tem permissão para acessar esta área.</h2>";
    exit;
}

// CPF do funcionário que será editado
$cpf = $_GET['cpf'] ?? '';

if (empty($cpf)) {
    echo "<h2 style='color:red; text-align:center;'>❌ Funcionário não especificado.</h2>";
    exit;
}

// Buscar dados do funcionário
$stmt = $conn->prepare("
    SELECT nome, cargo_id 
    FROM funcionarios 
    WHERE cpf = ?
");
$stmt->bind_param("s", $cpf);
$stmt->execute();
$dados = $stmt->get_result()->fetch_assoc();

if (!$dados) {
    echo "<h2 style='color:red; text-align:center;'>❌ Funcionário não encontrado.</h2>";
    exit;
}

$nomeFuncionario = $dados['nome'];

/*
    LISTA FINAL DE MÓDULOS
*/
$modulosPermitidos = [

    // Módulos administrativos (menu Gestão)
    'gestao_painel_chamados' => '🛠️ Painel de Chamados',
    'gestao_relatorios'      => '📄 Relatórios',
    'gestao_funcionarios'    => '👥 Funcionários',
    'gestao_lojas'           => '🏬 Lojas',
    'gestao_acessos'         => '🔐 Gestão de Acessos',
    'gestao_compras_externas' => '🛒 Gestão de Compras Externas',

    // MÓDULO COMPLETO DE CARTÕES (GESTÃO)
    'cartoes'                => '💳 Gestão de Cartões Corporativos',

    // Módulos de setores (Pendências)
    'setor_entregas'         => '🚚 Setor Entregas',
    'setor_vendas'           => '🛒 Setor Vendas',
    'setor_diretoria'        => '🏛️ Setor Diretoria',
    'setor_prevencao'        => '🛡️ Setor Prevenção e Perdas',
    'setor_regulatorio'      => '⚖️ Setor Regulatório',
    'setor_marketing'        => '📢 Setor Marketing',
    'setor_convenios'        => '🤝 Setor Convênios',
    'setor_contabilidade'    => '📊 Setor Contabilidade',
    'setor_escritorio'       => '🏢 Setor Escritório',

    // Ferramentas
    'ferramentas_avaliacoes'            => '🏪 Avaliação de Loja',
    'ferramentas_auditoria'             => '📝 Auditoria (Antiga)',
    'ferramentas_auditoria_pp'          => '🛡️ Auditoria PP',
    'ferramentas_auditoria_checklist'   => '📋 Auditoria Checklist',
    'ferramentas_inventario'            => '📦 Inventário',
    'ferramentas_controlados'           => '💊 Controlados',
    'ferramentas_controlados_farmaceutico' => '💊 Controlados Farmacêutico',
    'ferramentas_compras_externas' => '🛒 Compras Externas',

    // Permissões especiais
    'acesso_painel_loja'     => '🏪 Acesso ao painel da própria loja',
    'trilho_motoboy'         => '🛵 MotoBoy do Trilho'
];


// Carregar acessos atuais
$acessosAtuais = array_fill_keys(array_keys($modulosPermitidos), false);

$stmt = $conn->prepare("
    SELECT modulo, acesso 
    FROM acessos_usuarios 
    WHERE cpf = ?
");
$stmt->bind_param("s", $cpf);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    if (isset($acessosAtuais[$row['modulo']])) {
        $acessosAtuais[$row['modulo']] = intval($row['acesso']) === 1;
    }
}

// ===============================
// SALVAR ALTERAÇÕES
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ativados = 0;
    $desativados = 0;

    foreach ($modulosPermitidos as $modulo => $label) {
        $novoValor = isset($_POST['acesso_' . $modulo]) ? 1 : 0;
        $antigoValor = $acessosAtuais[$modulo] ? 1 : 0;

        if ($novoValor === 1 && $antigoValor === 0) $ativados++;
        if ($novoValor === 0 && $antigoValor === 1) $desativados++;
    }

    // Apagar acessos antigos
    $stmtDel = $conn->prepare("DELETE FROM acessos_usuarios WHERE cpf = ?");
    $stmtDel->bind_param("s", $cpf);
    $stmtDel->execute();

    // Inserir novos acessos
    $stmt = $conn->prepare("
        INSERT INTO acessos_usuarios (cpf, modulo, acesso)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE acesso = VALUES(acesso)
    ");

    foreach ($modulosPermitidos as $modulo => $label) {
        $acesso = isset($_POST['acesso_' . $modulo]) ? 1 : 0;
        $stmt->bind_param("ssi", $cpf, $modulo, $acesso);
        $stmt->execute();
    }

    header("Location: editar_acessos.php?cpf=$cpf&sucesso=1&ativados=$ativados&desativados=$desativados");
    exit;
}

// ===============================
// INÍCIO DO CONTEÚDO
// ===============================
ob_start();
?>

<link rel="stylesheet" href="../css/acessos.css">

<h2>🔐 Editar Acessos — <strong><?= htmlspecialchars($nomeFuncionario) ?></strong></h2>

<form method="POST">
    <table class="tabela-funcionarios">

        <tr><th colspan="2" class="secao-titulo">📊 Acessos de Gestão</th></tr>
        <?php foreach ($modulosPermitidos as $modulo => $label): ?>
            <?php if (str_starts_with($modulo, 'gestao_') || $modulo === 'cartoes'): ?>
                <tr>
                    <td><?= $label ?></td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" name="acesso_<?= $modulo ?>" <?= $acessosAtuais[$modulo] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>

        <tr><th colspan="2" class="secao-titulo">🧭 Acessos a Setores (Pendências)</th></tr>
        <?php foreach ($modulosPermitidos as $modulo => $label): ?>
            <?php if (str_starts_with($modulo, 'setor_')): ?>
                <tr>
                    <td><?= $label ?></td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" name="acesso_<?= $modulo ?>" <?= $acessosAtuais[$modulo] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>

        <tr><th colspan="2" class="secao-titulo">🧰 Ferramentas</th></tr>
        <?php foreach ($modulosPermitidos as $modulo => $label): ?>
            <?php if (str_starts_with($modulo, 'ferramentas_')): ?>
                <tr>
                    <td><?= $label ?></td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" name="acesso_<?= $modulo ?>" <?= $acessosAtuais[$modulo] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>

        <tr><th colspan="2" class="secao-titulo">⚙️ Permissões Especiais</th></tr>
        <?php foreach ($modulosPermitidos as $modulo => $label): ?>
            <?php if (in_array($modulo, ['acesso_painel_loja', 'trilho_motoboy'])): ?>
                <tr>
                    <td><?= $label ?></td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" name="acesso_<?= $modulo ?>" <?= $acessosAtuais[$modulo] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>

    </table>

    <button type="submit" class="btn" style="margin-top:15px;">💾 Salvar Acessos</button>
    <a class="btn" href="gerenciar_acessos.php" style="margin-left:10px;">🔙 Voltar</a>
</form>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
