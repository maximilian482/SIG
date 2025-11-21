<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Nova Loja</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<h2>➕ Cadastrar nova loja</h2>

<form method="POST" action="salvar_loja.php" style="max-width:500px;">
  <label>Nome da loja:</label><br>
  <input type="text" name="nome" required><br><br>

  <label>CNPJ:</label><br>
  <input type="text" name="cnpj" pattern="\d{14}" title="Digite os 14 números do CNPJ" required><br><br>

  <label>Responsável:</label><br>
  <input type="text" name="responsavel" required><br><br>

  <button type="submit">Salvar loja</button>
  <a class="btn" href="lojas.php" style="margin-left:10px;">Cancelar</a>
</form>

</body>
</html>
