<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

$cargoSessao = strtolower($_SESSION['cargo'] ?? '');

// SUPER e CEO têm acesso total
if (!in_array($cargoSessao, ['super', 'ceo'])) {
  header('Location: ../index.php');
  exit;
}

// Buscar cargos diretamente do banco
$sql = "
    SELECT DISTINCT LOWER(c.nome_cargo) AS cargo
    FROM funcionarios f
    INNER JOIN cargos c ON f.cargo_id = c.id
    WHERE f.desligamento IS NULL
";
$res = $conn->query($sql);

$cargos = [];
while ($row = $res->fetch_assoc()) {

    $c = strtolower(trim($row['cargo']));
    $cNorm = preg_replace('/[^a-z]/', '', $c); // remove acentos e caracteres especiais

    // Lista de cargos proibidos (CEO e SUPER)
    $bloqueados = [
        'ceo', 'diretor', 'diretorexecutivo', 'presidente',
        'super', 'superintendente', 'superadmin'
    ];

    // Se o cargo normalizado estiver na lista, bloquear
    if (in_array($cNorm, $bloqueados)) {
        continue;
    }

    // Evitar duplicações
    $cargos[$c] = ucfirst($c);
}

// Carregar acessos padrão
$acessosPadrao = json_decode(@file_get_contents('../dados/acessos_padrao.json'), true) ?: [];

$modulosDisponiveis = [
  'meus_chamados' => '📥 Meus Chamados',
  'chamados_supervisao' => '🧭 Chamados Supervisão',
  'chamados_ti' => '🖥️ Chamados TI',
  'chamados_manutencao' => '🔧 Chamados Manutenção',
  'painel_chamados' => '📊 Painel de Chamados',
  'inconformidade_lojas' => '🏬 Inconformidade Lojas',
  'relatorios' => '📄 Relatórios',
  'cadastro_funcionarios' => '👥 Funcionários',
  'lojas' => '🏬 Lojas',
  'inventario' => '📦 Inventário',
  'gerenciar_acessos' => '🔐 Gestão de Acessos'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $padraoRecebido = $_POST['padrao'] ?? [];

  foreach ($padraoRecebido as $cargo => $modulos) {
    foreach ($modulosDisponiveis as $chave => $rotulo) {
      $padraoRecebido[$cargo][$chave] = isset($modulos[$chave]);
    }
  }

  file_put_contents('../dados/acessos_padrao.json', json_encode($padraoRecebido, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
  echo "<p style='color:green;'>✅ Acessos padrão atualizados com sucesso.</p>";
  $acessosPadrao = $padraoRecebido;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Acessos Padrão por Cargo</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<h2>⚙️ Acessos Padrão por Cargo</h2>
<p>Defina os módulos que cada cargo deve acessar por padrão.</p>

<form method="POST">
  <?php foreach ($cargos as $cargo => $rotulo): ?>
    <fieldset style="margin-bottom:20px;">
      <legend><strong><?= htmlspecialchars($rotulo) ?></strong></legend>
      <?php foreach ($modulosDisponiveis as $chave => $rotuloModulo): ?>
        <?php $ativo = !empty($acessosPadrao[$cargo][$chave]); ?>
        <label style="display:block; margin-bottom:6px;">
          <input type="checkbox" name="padrao[<?= $cargo ?>][<?= $chave ?>]" <?= $ativo ? 'checked' : '' ?>>
          <?= $rotuloModulo ?>
        </label>
      <?php endforeach; ?>
    </fieldset>
  <?php endforeach; ?>
  <button type="submit">💾 Salvar padrões</button>
</form>

<a class="btn" href="gerenciar_acessos.php" style="margin-top:20px;">🔙 Voltar à gestão individual</a>
<a class="btn" href="../index.php" style="margin-top:10px;">🏠 Voltar ao painel</a>

</body>
</html>
