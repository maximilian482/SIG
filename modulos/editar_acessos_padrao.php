<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

$cpfFuncionarioAtual = $_SESSION['cpf'] ?? '';
if (!temAcesso($conn, $cpfFuncionarioAtual, 'gerenciar_acessos')) {
  echo "❌ Você não tem permissão para editar acessos.";
  exit;
}

// Lista de módulos disponíveis
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

// Função para normalizar o nome do cargo
function normalizarCargo($texto) {
  $texto = strtolower($texto);
  $texto = str_replace(
    ['á','à','ã','â','é','ê','í','ó','ô','õ','ú','ç'],
    ['a','a','a','a','e','e','i','o','o','o','u','c'],
    $texto
  );
  return preg_replace('/[^a-z]/', '', $texto);
}

// Buscar lista de cargos disponíveis
$cargos = [];
$res = $conn->query("SELECT DISTINCT nome_cargo FROM cargos ORDER BY nome_cargo ASC");
while ($row = $res->fetch_assoc()) {
  $cargos[] = $row['nome_cargo'];
}

// Cargo selecionado
$cargoSelecionado = $_GET['cargo'] ?? '';
$cargoNormalizado = normalizarCargo($cargoSelecionado);
$cpfPadrao = 'padrao:' . $cargoNormalizado;

// Salvar acessos padrão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($cargoSelecionado)) {
  $stmtDel = $conn->prepare("DELETE FROM acessos_usuarios WHERE cpf = ?");
  $stmtDel->bind_param("s", $cpfPadrao);
  $stmtDel->execute();

  $stmt = $conn->prepare("
    INSERT INTO acessos_usuarios (cpf, modulo, acesso)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE acesso = VALUES(acesso)
  ");

  foreach ($modulosDisponiveis as $modulo => $label) {
    $acesso = isset($_POST['acesso_' . $modulo]) ? 1 : 0;
    $stmt->bind_param("ssi", $cpfPadrao, $modulo, $acesso);
    $stmt->execute();
  }

  header("Location: editar_acessos_padrao.php?cargo=" . urlencode($cargoSelecionado) . "&sucesso=1");
  exit;
}

// Carregar acessos padrão atuais
$acessosPadrao = array_fill_keys(array_keys($modulosDisponiveis), false);
if (!empty($cargoSelecionado)) {
  $stmt = $conn->prepare("SELECT modulo, acesso FROM acessos_usuarios WHERE cpf = ?");
  $stmt->bind_param("s", $cpfPadrao);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $modulo = $row['modulo'];
    if (isset($acessosPadrao[$modulo]) && intval($row['acesso']) === 1) {
      $acessosPadrao[$modulo] = true;
    }
  }
  $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Editar Acessos Padrão</title>
  <link rel="stylesheet" href="../css/acessos.css">
</head>
<body>

<h2>⚙️ Editar Acessos Padrão por Cargo</h2>

<?php if (empty($cargoSelecionado)): ?>
  <form method="GET">
    <label for="cargo">Selecione um cargo:</label>
    <select name="cargo" id="cargo" onchange="this.form.submit()">
      <option value="">-- Escolha --</option>
      <?php foreach ($cargos as $cargo): ?>
        <option value="<?= htmlspecialchars($cargo) ?>"><?= htmlspecialchars($cargo) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
<?php else: ?>
  <p><strong>Cargo selecionado:</strong> <?= htmlspecialchars($cargoSelecionado) ?></p>

  <form method="POST">
    <table>
      <tr><th>Módulo</th><th>Acesso padrão</th></tr>
      <?php foreach ($modulosDisponiveis as $modulo => $label): ?>
        <tr>
          <td><?= $label ?></td>
          <td>
            <label class="switch">
              <input type="checkbox" name="acesso_<?= $modulo ?>" <?= $acessosPadrao[$modulo] ? 'checked' : '' ?>>
              <span class="slider"></span>
            </label>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
    <button type="submit" style="margin-top:10px;">💾 Salvar padrão</button>
    <a class="btn" href="gerenciar_acessos.php" style="margin-left:10px;">🔙 Voltar</a>
  </form>

  <?php if (isset($_GET['sucesso'])): ?>
    <div class="alerta-sucesso">✅ Acessos padrão atualizados com sucesso!</div>
  <?php endif; ?>
<?php endif; ?>

</body>
</html>
