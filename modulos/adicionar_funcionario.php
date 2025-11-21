<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

// Inicializar variáveis para evitar erros
$erros = $erros ?? [];   // se não vier nada, vira array vazio
$dados = $dados ?? [];   // se não vier nada, vira array vazio

// Carregar cargos do banco
$cargos = [];
$resCargos = $conn->query("SELECT id, nome_cargo FROM cargos ORDER BY nome_cargo");
while ($row = $resCargos->fetch_assoc()) {
  $cargos[$row['id']] = $row['nome_cargo'];
}

// Carregar lojas do banco
$lojas = [];
$resLojas = $conn->query("SELECT id, nome FROM lojas ORDER BY nome");
while ($row = $resLojas->fetch_assoc()) {
  $lojas[$row['id']] = $row['nome'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Adicionar Funcionário</title>
  <link rel="stylesheet" href="../css/.css">
  <style>
    :root {
  --verde-palmeiras: #1E513D;
  --verde-hover: #15402f;
  --cinza-fundo: #F4F6F8;
  --cinza-borda: #DDE2E5;
  --texto-principal: #1C1C1C;
  --branco: #FFFFFF;
  --erro-bg: #f8d7da;
  --erro-texto: #721c24;
}

body {
  background-color: var(--cinza-fundo);
  font-family: 'Segoe UI', sans-serif;
  color: var(--texto-principal);
  margin: 0;
  padding: 20px;
}

h2 {
  text-align: center;
  color: var(--verde-palmeiras);
  margin-bottom: 20px;
}

form {
  background: var(--branco);
  padding: 25px;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  margin: 0 auto;
  max-width: 600px;
}

label {
  font-weight: bold;
  display: block;
  margin-bottom: 6px;
  color: var(--verde-palmeiras);
}

input, select {
  width: 100%;
  padding: 10px;
  border: 1px solid var(--cinza-borda);
  border-radius: 6px;
  font-size: 1em;
  margin-bottom: 15px;
  box-sizing: border-box;
}

input:focus, select:focus {
  border-color: var(--verde-palmeiras);
  outline: none;
  box-shadow: 0 0 5px rgba(30,81,61,0.3);
}

.erro-campo {
  border-color: var(--erro-texto);
  background-color: var(--erro-bg);
}

.btn-filtro {
  background-color: var(--verde-palmeiras);
  color: var(--branco);
  padding: 12px 20px;
  border-radius: 6px;
  text-decoration: none;
  font-weight: bold;
  font-size: 1em;
  transition: background-color 0.2s ease;
  display: inline-block;
  text-align: center;
}

.btn-filtro:hover {
  background-color: var(--verde-hover);
}

@media (max-width: 600px) {
  body {
    padding: 10px;
  }
  form {
    padding: 15px;
    max-width: 100%;
  }
  input, select {
    font-size: 0.95em;
    padding: 8px;
  }
  .btn-filtro {
    width: 100%;
    margin-bottom: 10px;
  }
}

  </style>
</head>
<body>

<?php if (!empty($erros)): ?>
  <div style="background:#f8d7da; color:#721c24; padding:10px; border-radius:4px; margin-bottom:20px;">
    <?php foreach ($erros as $erro): ?>
      <p><?= htmlspecialchars($erro) ?></p>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<h2>➕ Adicionar novo funcionário</h2>

<form method="POST" action="salvar_funcionario.php" style="max-width:500px;">
  <label>Código Manual (Cod Vetor):</label><br>
  <input type="text" name="codigo" value="<?= htmlspecialchars($dados['codigo'] ?? '') ?>"><br><br>

  <label>CC: (Contabilidade)</label><br>
  <input type="text" name="cc" value="<?= htmlspecialchars($dados['cc'] ?? '') ?>"><br><br>

  <label>Nome:</label><br>
  <input type="text" name="nome"
         value="<?= htmlspecialchars($dados['nome'] ?? '') ?>"
         required
         class="<?= in_array('❌ O campo "nome" é obrigatório.', $erros) ? 'erro-campo' : '' ?>"><br><br>

  <label>Endereço:</label><br>
  <input type="text" name="endereco" value="<?= htmlspecialchars($dados['endereco'] ?? '') ?>"><br><br>

  <label>CPF:</label><br>
  <input type="text" name="cpf" pattern="\d{11}" title="Digite os 11 números do CPF"
         value="<?= htmlspecialchars($dados['cpf'] ?? '') ?>" required><br><br>

  <label>Cargo:</label><br>
  <select name="cargo_id" required>
    <option value="" disabled <?= empty($dados['cargo_id']) ? 'selected' : '' ?>>Selecione</option>
    <?php foreach ($cargos as $id => $cargo): ?>
      <option value="<?= $id ?>" <?= ($dados['cargo_id'] ?? '') == $id ? 'selected' : '' ?>>
        <?= htmlspecialchars($cargo) ?>
      </option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Loja:</label><br>
  <select name="loja_id" required>
    <option value="" disabled <?= empty($dados['loja_id']) ? 'selected' : '' ?>>Selecione</option>
    <?php foreach ($lojas as $id => $nome): ?>
      <option value="<?= $id ?>" <?= ($dados['loja_id'] ?? '') == $id ? 'selected' : '' ?>>
        <?= htmlspecialchars($nome) ?>
      </option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Email:</label><br>
  <input type="email" name="email" value="<?= htmlspecialchars($dados['email'] ?? '') ?>"><br><br>

  <label>Data de contratação:</label><br>
  <input type="date" name="contratacao" value="<?= htmlspecialchars($dados['contratacao'] ?? '') ?>" required><br><br>

  <label>Aniversário:</label><br>
  <input type="date" name="aniversario" value="<?= htmlspecialchars($dados['aniversario'] ?? '') ?>"><br><br>

  <label>Telefone:</label><br>
  <input type="text" name="telefone" placeholder="(99) 99999-9999"
         value="<?= htmlspecialchars($dados['telefone'] ?? '') ?>"><br><br>

  <input type="hidden" name="ativo" value="1">

  <button type="submit" class="btn-filtro">💾 Salvar</button>
  <a class="btn-filtro" href="funcionarios.php" style="margin-left:10px;">❌ Cancelar</a>
</form>

</body>
</html>
