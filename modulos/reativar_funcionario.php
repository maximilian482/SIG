<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Reativar Funcionário</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php
$loja = $_GET['loja'] ?? '';
$id = $_GET['id'] ?? '';

$funcionarios = json_decode(file_get_contents('../dados/funcionarios.json'), true);
$cargos = json_decode(file_get_contents('../dados/cargos.json'), true);
$lojas = json_decode(file_get_contents('../dados/gerencial.json'), true);

if (!isset($funcionarios[$loja][$id])) {
  echo "<p>Funcionário não encontrado.</p>";
  echo '<a class="btn" href="funcionarios_inativos.php">🔙 Voltar</a>';
  exit;
}

$f = $funcionarios[$loja][$id];
?>

<h2>✅ Reativar funcionário: <?= htmlspecialchars($f['nome']) ?></h2>

<form method="POST" action="salvar_reativacao.php" style="max-width:500px;">
  <input type="hidden" name="id" value="<?= $id ?>">
  <input type="hidden" name="loja_original" value="<?= htmlspecialchars($loja) ?>">

  <label>Nome:</label><br>
  <input type="text" name="nome" value="<?= htmlspecialchars($f['nome']) ?>" required><br><br>

  <label>CPF:</label><br>
  <input type="text" name="cpf" value="<?= htmlspecialchars($f['cpf'] ?? '') ?>" pattern="\d{11}" required><br><br>

  <label>Cargo:</label><br>
  <select name="cargo" required>
    <?php foreach ($cargos as $cargo): ?>
      <option value="<?= $cargo ?>" <?= ($f['cargo'] ?? '') === $cargo ? 'selected' : '' ?>><?= $cargo ?></option>
    <?php endforeach; ?>
    <option value="Gerente" <?= ($f['cargo'] ?? '') === 'Gerente' ? 'selected' : '' ?>>Gerente</option>
  </select><br><br>

  <label>Loja:</label><br>
  <select name="loja" required>
    <?php foreach ($lojas as $idLoja => $dadosLoja): ?>
      <option value="<?= $idLoja ?>" <?= $idLoja === $loja ? 'selected' : '' ?>><?= htmlspecialchars($dadosLoja['nome'] ?? $idLoja) ?></option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Email:</label><br>
  <input type="email" name="email" value="<?= htmlspecialchars($f['email'] ?? '') ?>"><br><br>

  <label>Nova data de contratação:</label><br>
  <input type="date" name="contratacao" required><br><br>

  <label>Aniversário:</label><br>
  <input type="date" name="aniversario" value="<?= htmlspecialchars($f['aniversario'] ?? '') ?>"><br><br>

  <label>Telefone:</label><br>
  <input type="text" name="telefone" value="<?= htmlspecialchars($f['telefone'] ?? '') ?>"><br><br>

  <button type="submit">Confirmar reativação</button>
  <a class="btn" href="funcionarios_inativos.php" style="margin-left:10px;">Cancelar</a>
</form>

</body>
</html>
