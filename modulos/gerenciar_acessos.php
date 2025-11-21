<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

$cpfFuncionarioAtual = $_SESSION['cpf'] ?? '';

$buscaNome = strtolower(trim($_GET['busca'] ?? ''));
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$limite = max(5, intval($_GET['limite'] ?? 10));
$offset = ($pagina - 1) * $limite;

$buscaNomeLike = '%' . $buscaNome . '%';

// Consulta paginada
$stmt = $conn->prepare("
  SELECT f.nome, f.cpf, c.nome_cargo AS cargo, l.nome AS loja
  FROM funcionarios f
  LEFT JOIN cargos c ON f.cargo_id = c.id
  LEFT JOIN lojas l ON f.loja_id = l.id
  WHERE f.desligamento IS NULL
    AND (LOWER(f.nome) LIKE ? OR ? = '')
    AND LOWER(c.nome_cargo) NOT IN ('super', 'ceo')
  ORDER BY f.nome ASC
  LIMIT ? OFFSET ?
");
$stmt->bind_param("ssii", $buscaNomeLike, $buscaNome, $limite, $offset);
$stmt->execute();
$result = $stmt->get_result();
$funcionarios = $result->fetch_all(MYSQLI_ASSOC);

// Total de registros para paginação
$stmtTotal = $conn->prepare("
  SELECT COUNT(*) FROM funcionarios f
  LEFT JOIN cargos c ON f.cargo_id = c.id
  WHERE f.desligamento IS NULL
    AND (LOWER(f.nome) LIKE ? OR ? = '')
    AND LOWER(c.nome_cargo) NOT IN ('super', 'ceo')
");
$stmtTotal->bind_param("ss", $buscaNomeLike, $buscaNome);
$stmtTotal->execute();
$total = $stmtTotal->get_result()->fetch_row()[0];
$totalPaginas = ceil($total / $limite);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Gerenciar Acessos</title>
  <link rel="stylesheet" href="../css/acessos.css">
  
</head>
<body>
<?php if (isset($_GET['senha_resetada']) && $_GET['senha_resetada'] == '1'): ?>
  <div class="alerta-sucesso">
    ✅ Senha redefinida com sucesso! A nova senha foi definida como os 6 primeiros dígitos do CPF.
  </div>
<?php endif; ?>

<h2>🔐 Gerenciar Acessos</h2>
<p>Controle os módulos disponíveis para cada funcionário.</p>

<form method="GET" class="filtro-form">
  <input type="text" name="busca" value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>" placeholder="Buscar por nome">
  <button type="submit">🔍 Filtrar</button>
  <a href="gerenciar_acessos.php" class="btn limpar-btn">🧹 Limpar filtros</a>
</form>
<br>
<a class="btn" href="editar_acessos_padrao.php">⚙️ EDITAR ACESSOS POR CARGO</a>

<table class="tabela-funcionarios">
  <thead>
  <tr>
    <th>Nome</th>
    <th>Cargo</th>
    <th>Loja</th>
    <th>Acessos</th>
    <th>Resetar Senha</th>
  </tr>
</thead>
<tbody>
  <?php foreach ($funcionarios as $f): ?>
    <tr>
      <td><?= htmlspecialchars($f['nome']) ?></td>
      <td><?= htmlspecialchars($f['cargo']) ?></td>
      <td><?= htmlspecialchars($f['loja'] ?? '—') ?></td>
      <td>
        <a class="btn" href="editar_acessos.php?cpf=<?= urlencode($f['cpf']) ?>">✏️ Editar acessos</a>
      </td>
      <td>
        <a class="btn" 
          href="../perfil/resetar_senha.php?cpf=<?= urlencode($f['cpf']) ?>" 
          title="A nova senha será os 6 primeiros dígitos do CPF"
          onclick="return confirm('Deseja realmente resetar a senha deste usuário?')">
          🔁 Resetar senha
        </a>
      </td>
    </tr>
  <?php endforeach; ?>
</tbody>

</table>

<div class="paginacao-container">
  <div class="paginacao">
    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
      <a href="?pagina=<?= $i ?>&limite=<?= $limite ?>&busca=<?= urlencode($buscaNome) ?>"
         class="<?= $i == $pagina ? 'ativo' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <form method="GET" class="limite-form">
    <input type="hidden" name="busca" value="<?= htmlspecialchars($buscaNome) ?>">
    <input type="hidden" name="pagina" value="<?= $pagina ?>">
    <label for="limite">Exibir:</label>
    <select name="limite" onchange="this.form.submit()">
      <option value="5" <?= $limite == 5 ? 'selected' : '' ?>>5</option>
      <option value="10" <?= $limite == 10 ? 'selected' : '' ?>>10</option>
      <option value="20" <?= $limite == 20 ? 'selected' : '' ?>>20</option>
    </select>
  </form>
</div>

<a class="btn" href="../index.php" style="margin-top:20px;">🏠 Voltar ao início</a>
<a class="btn" href="/modulos/gestao.php" style="margin-top:20px;">🔙 Voltar</a>

</body>
</html>
