<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Editar Loja</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php
$nomeLoja = $_GET['nome'] ?? '';
$arquivo = '../dados/gerencial.json';
$dados = file_exists($arquivo) ? json_decode(file_get_contents($arquivo), true) : [];

$loja = $dados[$nomeLoja] ?? null;

if (!$loja) {
  echo "<p>Loja não encontrada.</p>";
  echo '<a class="btn" href="lojas.php">🔙 Voltar para lista de lojas</a>';
  exit;
}

$cert = $loja['certificado_digital'] ?? [];
?>

<h2>✏️ Editar loja: <?= htmlspecialchars($loja['nome'] ?? $nomeLoja) ?></h2>

<form method="POST" action="salvar_edicao_loja.php" style="max-width:500px;">
  <input type="hidden" name="id" value="<?= htmlspecialchars($nomeLoja) ?>">

  <label>Nome da loja:</label><br>
  <input type="text" name="nome" value="<?= htmlspecialchars($loja['nome'] ?? '') ?>" required><br><br>

  <label>CNPJ:</label><br>
  <input type="text" name="cnpj" value="<?= htmlspecialchars($loja['cnpj'] ?? '') ?>" pattern="\d{14}" title="Digite os 14 números do CNPJ" required><br><br>

  <label>Responsável:</label><br>
  <input type="text" name="responsavel" value="<?= htmlspecialchars($loja['responsavel'] ?? '') ?>" required><br><br>

  <label>Validade do certificado digital:</label><br>
  <input type="date" name="validade" value="<?= htmlspecialchars($cert['validade'] ?? '') ?>"><br><br>

  <button type="submit">Salvar alterações</button>
  <a class="btn" href="loja.php?nome=<?= urlencode($nomeLoja) ?>" style="margin-left:10px;">Cancelar</a>
</form>

</body>
</html>
