<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

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
    // Módulos administrativos (ativam menu Gestão)
    'gestao_painel_chamados' => '🛠️ Painel de Chamados',
    'gestao_relatorios'      => '📄 Relatórios',
    'gestao_funcionarios'    => '👥 Funcionários',
    'gestao_inventario'      => '📦 Inventário',
    'gestao_lojas'           => '🏬 Lojas',
    'gestao_acessos'         => '🔐 Gestão de Acessos',

    // Módulos de setor (ativam Pendências)
    'setor_entregas'         => '🚚 Setor Entregas',
    'setor_vendas'           => '🛒 Setor Vendas',
    'setor_diretoria'        => '🏛️ Setor Diretoria',
    'setor_prevencao'        => '🛡️ Setor Prevenção e Perdas',
    'setor_regulatorio'      => '⚖️ Setor Regulatório',
    'setor_marketing'        => '📢 Setor Marketing',
    'setor_convenios'        => '🤝 Setor Convênios',
    'setor_contabilidade'    => '📊 Setor Contabilidade',
    'setor_escritorio'       => '🏢 Setor Escritório',

    // Permissões especiais
    'acesso_painel_loja'     => '🏪 Acesso ao painel da própria loja',
    'trilho_motoboy' => '🛵 Motoboy do Trilho'

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

// Salvar alterações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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

    header("Location: editar_acessos.php?cpf=$cpf&sucesso=1");
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Acessos</title>
    <link rel="stylesheet" href="../css/acessos.css">
</head>
<body>

<h2>🔐 Editar Acessos — <strong><?= htmlspecialchars($nomeFuncionario) ?></strong></h2>

<form method="POST">
    <table>

    <!-- ============================
         SEÇÃO: ACESSOS DE GESTÃO
    ============================= -->
    <tr><th colspan="2" class="secao-titulo">📊 Acessos de Gestão</th></tr>

    <?php foreach ($modulosPermitidos as $modulo => $label): ?>
        <?php if (str_starts_with($modulo, 'gestao_')): ?>
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


    <!-- ============================
         SEÇÃO: ACESSOS A SETORES
    ============================= -->
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


    <!-- ============================
         SEÇÃO: PERMISSÕES ESPECIAIS
    ============================= -->
    <tr><th colspan="2" class="secao-titulo">⚙️ Permissões Especiais</th></tr>

    <?php foreach ($modulosPermitidos as $modulo => $label): ?>
        <?php if (in_array($modulo, ['acesso_pendencias', 'acesso_painel_loja', 'trilho_motoboy'])): ?>
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


    <button type="submit" style="margin-top:10px;">💾 Salvar Acessos</button>
    <a class="btn" href="gerenciar_acessos.php" style="margin-left:10px;">🔙 Voltar</a>
</form>

<?php if (isset($_GET['sucesso'])): ?>
    <div class="alerta-sucesso">
        ✅ Acessos atualizados com sucesso!
    </div>
<?php endif; ?>

</body>
</html>
