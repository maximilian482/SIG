<?php
session_start();
require_once '../dados/conexao.php';

// Inicializa conexão
$conn = conectar();
if (!$conn) {
  echo "<p>❌ Falha ao conectar ao banco de dados.</p>";
  echo '<a class="btn" href="funcionarios.php">🔙 Voltar</a>';
  exit;
}

$id   = intval($_GET['id'] ?? 0);
$loja = intval($_GET['loja'] ?? 0);

if ($id <= 0 || $loja <= 0) {
  echo "<p>❌ Parâmetros inválidos.</p>";
  echo '<a class="btn" href="funcionarios.php">🔙 Voltar</a>';
  exit;
}

// Buscar funcionário
$sql = "
  SELECT f.*, l.nome AS nome_loja, c.nome_cargo AS nome_cargo
  FROM funcionarios f
  LEFT JOIN lojas l ON f.loja_id = l.id
  LEFT JOIN cargos c ON f.cargo_id = c.id
  WHERE f.id = ? AND f.loja_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $id, $loja);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo "<p>❌ Funcionário não encontrado.</p>";
  echo '<a class="btn" href="funcionarios.php">🔙 Voltar</a>';
  exit;
}

$f = $result->fetch_assoc();

// Carregar cargos
$cargos = [];
$resCargos = $conn->query("SELECT id, nome_cargo FROM cargos ORDER BY nome_cargo");
while ($row = $resCargos->fetch_assoc()) {
  $cargos[$row['id']] = $row['nome_cargo'];
}

// Carregar lojas
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
  <title>Editar Funcionário</title>
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

<h2>✏️ Editar funcionário: <?= htmlspecialchars($f['nome']) ?></h2>

<form method="POST" action="salvar_edicao_funcionario.php" style="max-width:500px;">
  <input type="hidden" name="id" value="<?= $f['id'] ?>">
  <input type="hidden" name="loja_original" value="<?= $f['loja_id'] ?>">

  <label>Cód Vetor:</label><br>
  <input type="text" name="codigo" value="<?= htmlspecialchars($f['codigo'] ?? '') ?>" required><br><br>

  <label>CC: (Contabilidade)</label><br>
  <input type="text" name="cc" value="<?= htmlspecialchars($f['cc'] ?? '') ?>" required><br><br>

  <label>Nome:</label><br>
  <input type="text" name="nome" value="<?= htmlspecialchars($f['nome']) ?>" required><br><br>

  <label>Endereço:</label><br>
  <input type="text" name="endereco" value="<?= htmlspecialchars($f['endereco'] ?? '') ?>"><br><br>

  <label>CPF:</label><br>
  <input type="text" name="cpf" value="<?= htmlspecialchars($f['cpf'] ?? '') ?>" pattern="\d{11}" required><br><br>

  <label>Cargo:</label><br>
  <select name="cargo_id" required>
    <option value="" disabled>Selecione</option>
    <?php foreach ($cargos as $idCargo => $nomeCargo): ?>
      <option value="<?= $idCargo ?>" <?= $idCargo == $f['cargo_id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($nomeCargo) ?>
      </option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Loja:</label><br>
  <select name="loja_id" required>
    <option value="" disabled>Selecione</option>
    <?php foreach ($lojas as $idLoja => $nomeLoja): ?>
      <option value="<?= $idLoja ?>" <?= $idLoja == $f['loja_id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($nomeLoja) ?>
      </option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Email:</label><br>
  <input type="email" name="email" value="<?= htmlspecialchars($f['email'] ?? '') ?>"><br><br>

  <label>Data de contratação:</label><br>
  <input type="date" name="contratacao" value="<?= htmlspecialchars($f['contratacao'] ?? '') ?>"><br><br>

  <label>Data de nascimento:</label><br>
  <input type="date" name="aniversario" value="<?= htmlspecialchars($f['nascimento'] ?? '') ?>"><br><br>

  <label>Telefone:</label><br>
  <input type="text" name="telefone" value="<?= htmlspecialchars($f['telefone'] ?? '') ?>"><br><br>

  <button type="submit" class="btn-filtro">💾 Salvar alterações</button>
  <a class="btn-filtro" href="funcionarios.php" style="margin-left:10px;">❌ Cancelar</a>
</form>

</body>
</html>
