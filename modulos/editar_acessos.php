<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

$cpfFuncionarioAtual = $_SESSION['cpf'] ?? '';

if (empty($cpfFuncionarioAtual)) {
  echo "❌ CPF do funcionário não encontrado na sessão.";
  exit;
}

if (!temAcesso($conn, $cpfFuncionarioAtual, 'gerenciar_acessos')) {
  echo "❌ Você não tem permissão para gerenciar acessos.";
  exit;
}

$modulosDisponiveis = [
  'chamados_supervisao' => '🧭 Chamados Supervisão',
  'chamados_ti' => '🖥️ Chamados TI',
  'chamados_manutencao' => '🔧 Chamados Manutenção',
  'painel_chamados' => '📊 Painel de Chamados',
  'inconformidade_lojas' => '🏬 Inconformidade Lojas',
  'relatorios' => '📄 Relatórios',
  'cadastro_funcionarios' => '👥 Funcionários',
  'lojas' => '🏬 Lojas',
  'inventario' => '📦 Inventário',
  'gerenciar_acessos' => '🔐 Gestão de Acessos',
  'painel_loja_gerente' => '🏪 Loja (Gerente)',
  'painel_tratamento_inconformidades' => '🛠️ Tratar Inconformidades'
];

$cpfSelecionado = $_GET['cpf'] ?? '';
$dadosFuncionario = null;

// Buscar dados do funcionário
$stmt = $conn->prepare("
  SELECT f.nome, f.cpf, c.nome_cargo AS cargo, l.nome AS loja
  FROM funcionarios f
  LEFT JOIN cargos c ON f.cargo_id = c.id
  LEFT JOIN lojas l ON f.loja_id = l.id
  WHERE f.desligamento IS NULL AND f.cpf = ?
");
$stmt->bind_param("s", $cpfSelecionado);
$stmt->execute();
$result = $stmt->get_result();
$dadosFuncionario = $result->fetch_assoc();

$cpfFuncionarioEditado = $dadosFuncionario['cpf'] ?? '';
function normalizarCargo($texto) {
  $texto = strtolower($texto);
  $texto = str_replace(
    ['á','à','ã','â','é','ê','í','ó','ô','õ','ú','ç'],
    ['a','a','a','a','e','e','i','o','o','o','u','c'],
    $texto
  );
  return preg_replace('/[^a-z]/', '', $texto); // remove espaços e símbolos
}

$cargoFuncionario = normalizarCargo($dadosFuncionario['cargo'] ?? '');

// Salvar acessos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cpf = trim($_POST['cpf']);

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

  foreach ($modulosDisponiveis as $modulo => $label) {
    $acesso = isset($_POST['acesso_' . $modulo]) ? 1 : 0;
    $stmt->bind_param("ssi", $cpf, $modulo, $acesso);
    $stmt->execute();
  }

  $modulosAtivos = [];
  foreach ($modulosDisponiveis as $modulo => $label) {
    if (isset($_POST['acesso_' . $modulo])) {
      $modulosAtivos[] = $label;
    }
  }
  $modulosEncoded = urlencode(json_encode($modulosAtivos));
  header("Location: editar_acessos.php?cpf=$cpf&sucesso=1&modulos=$modulosEncoded");
  exit;
}

// Carregar acessos do funcionário
$acessosFuncionario = array_fill_keys(array_keys($modulosDisponiveis), false);

// Acessos diretos
$stmt = $conn->prepare("SELECT modulo, acesso FROM acessos_usuarios WHERE cpf = ?");
$stmt->bind_param("s", $cpfFuncionarioEditado);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $modulo = $row['modulo'];
  if (isset($acessosFuncionario[$modulo]) && intval($row['acesso']) === 1) {
    $acessosFuncionario[$modulo] = true;
  }
}
$stmt->close();

// Acessos padrão por cargo (complementares)
$cpfPadrao = 'padrao:' . $cargoFuncionario;
$stmt2 = $conn->prepare("SELECT modulo, acesso FROM acessos_usuarios WHERE cpf = ?");
$stmt2->bind_param("s", $cpfPadrao);
$stmt2->execute();
$result2 = $stmt2->get_result();
while ($row = $result2->fetch_assoc()) {
  $modulo = $row['modulo'];
  if (isset($acessosFuncionario[$modulo]) && intval($row['acesso']) === 1) {
    if (!$acessosFuncionario[$modulo]) {
      $acessosFuncionario[$modulo] = true;
    }
  }
}
$stmt2->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Editar Acessos</title>
  <link rel="stylesheet" href="../css/acessos.css">
</head>
<body>

<h2>🔐 Editar Acessos</h2>

<?php if ($dadosFuncionario): ?>
  <p><strong>Funcionário:</strong> <?= htmlspecialchars($dadosFuncionario['nome']) ?> | <?= htmlspecialchars($dadosFuncionario['cargo']) ?> | <?= htmlspecialchars($dadosFuncionario['loja'] ?? '—') ?></p>

  <form method="POST">
    <input type="hidden" name="cpf" value="<?= htmlspecialchars($cpfSelecionado) ?>">
    <table>
      <tr><th>Módulo</th><th>Acesso</th></tr>
      <?php foreach ($modulosDisponiveis as $modulo => $label): ?>
        <tr>
          <td><?= $label ?></td>
          <td>
            <label class="switch">
              <input type="checkbox" name="acesso_<?= $modulo ?>" <?= $acessosFuncionario[$modulo] ? 'checked' : '' ?>>
              <span class="slider"></span>
            </label>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
    <button type="submit" style="margin-top:10px;">💾 Salvar acessos</button>
    <a class="btn" href="gerenciar_acessos.php" style="margin-left:10px;">🔙 Voltar</a>
    <a class="btn" href="editar_acessos_padrao.php?cargo=<?= urlencode($dadosFuncionario['cargo']) ?>" style="margin-left:10px;">⚙️ Editar padrão do cargo</a>
  </form>

  <?php if (isset($_GET['sucesso']) && isset($_GET['modulos'])): ?>
    <?php $modulosConcedidos = json_decode($_GET['modulos'], true); ?>
    <div class="alerta-sucesso">
      ✅ Acessos atualizados com sucesso!
      <ul>
        <?php foreach ($modulosConcedidos as $modulo): ?>
          <li>✔️ <?= htmlspecialchars($modulo) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

<?php else: ?>
  <p style="color:red;">Funcionário não encontrado ou inativo.</p>
  <a class="btn" href="gerenciar_acessos.php">🔙 Voltar</a>
<?php endif; ?>

</body>
</html>
