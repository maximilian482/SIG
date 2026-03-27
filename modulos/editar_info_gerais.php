<?php
session_start();
require_once '../dados/conexao.php';

$conn = conectar();

$cpf   = $_SESSION['cpf'] ?? '';
$cargo = strtolower($_SESSION['cargo'] ?? '');
$lojaId = intval($_GET['id'] ?? 0);

// Busca dados da loja
$stmt = $conn->prepare("SELECT * FROM lojas WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$result = $stmt->get_result();
$loja = $result->fetch_assoc();

if (!$loja) {
  echo "<p>❌ Loja não encontrada.</p>";
  exit;
}

// Busca funcionários ativos da loja
$stmt = $conn->prepare("
  SELECT f.id, f.nome, f.telefone, c.nome_cargo
  FROM funcionarios f
  JOIN cargos c ON f.cargo_id = c.id
  WHERE f.desligamento IS NULL AND f.loja_id = ?
  ORDER BY f.nome
");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$lista = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Atualiza dados da loja
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $nome = trim($_POST['nome'] ?? '');
  if ($nome === '') {
    die('❌ O campo "nome" da loja é obrigatório.');
  }

  $cnpj              = $_POST['cnpj'] ?? '';
  $inscricao_estadual = $_POST['inscricao_estadual'] ?? '';
  $endereco          = $_POST['endereco'] ?? '';
  $bairro            = $_POST['bairro'] ?? '';
  $cidade            = $_POST['cidade'] ?? '';
  $estado            = $_POST['estado'] ?? '';
  $cep               = $_POST['cep'] ?? '';
  $telefone_fixo     = $_POST['telefone_fixo'] ?? '';
  $celular           = $_POST['celular'] ?? '';
  $email_gmail       = $_POST['email_gmail'] ?? '';
  $email_corporativo = $_POST['email_corporativo'] ?? '';
  $dias_funcionamento= $_POST['dias_funcionamento'] ?? '';
  $observacoes       = $_POST['observacoes'] ?? '';

  $gerenteId = ($_POST['gerente_id'] ?? '0') !== '0' ? intval($_POST['gerente_id']) : null;
  $subgerenteId = ($_POST['subgerente_id'] ?? '0') !== '0' ? intval($_POST['subgerente_id']) : null;

  if ($gerenteId !== null && $gerenteId === $subgerenteId) {
    die('❌ Gerente e subgerente não podem ser a mesma pessoa.');
  }

  $stmt = $conn->prepare("
    UPDATE lojas SET
      nome = ?, cnpj = ?, inscricao_estadual = ?, endereco = ?, bairro = ?, cidade = ?, estado = ?, cep = ?,
      telefone_fixo = ?, celular = ?, email_gmail = ?, email_corporativo = ?, dias_funcionamento = ?, observacoes = ?,
      gerente_id = ?, subgerente_id = ?
    WHERE id = ?
  ");
  $stmt->bind_param(
    "sssssssssssssssii",
    $nome,
    $cnpj,
    $inscricao_estadual,
    $endereco,
    $bairro,
    $cidade,
    $estado,
    $cep,
    $telefone_fixo,
    $celular,
    $email_gmail,
    $email_corporativo,
    $dias_funcionamento,
    $observacoes,
    $gerenteId,
    $subgerenteId,
    $lojaId
  );

  $stmt->execute();
  header("Location: loja.php?id=" . $lojaId);
  exit;
}

function labelFuncionario($f) {
  $nome = $f['nome'] ?? '';
  $cargo = $f['nome_cargo'] ?? '';
  $tel = $f['telefone'] ?? '';
  $partes = [$nome];
  if ($cargo) $partes[] = "($cargo)";
  if ($tel)   $partes[] = "📞 $tel";
  return trim(implode(' ', $partes));
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Editar Informações Gerais</title>
  <link rel="stylesheet" href="../css/loja_form.css">
</head>
<body>

  <div class="form-container">
    <h2>✏️ Editar Informações — <?= htmlspecialchars($loja['nome']) ?></h2>

    <form method="post">

      <div class="form-group">
        <label>Nome da unidade</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($loja['nome']) ?>" required>
      </div>

      <div class="form-group">
        <label>CNPJ</label>
        <input type="text" name="cnpj" value="<?= htmlspecialchars($loja['cnpj']) ?>">
      </div>

      <div class="form-group">
        <label>Inscrição Estadual</label>
        <input type="text" name="inscricao_estadual" value="<?= htmlspecialchars($loja['inscricao_estadual'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label>Endereço</label>
        <input type="text" name="endereco" value="<?= htmlspecialchars($loja['endereco']) ?>">
      </div>

      <div class="form-group">
        <label>Bairro</label>
        <input type="text" name="bairro" value="<?= htmlspecialchars($loja['bairro']) ?>">
      </div>

      <div class="form-group">
        <label>Cidade</label>
        <input type="text" name="cidade" value="<?= htmlspecialchars($loja['cidade']) ?>">
      </div>

      <div class="form-group">
        <label>Estado</label>
        <input type="text" name="estado" value="<?= htmlspecialchars($loja['estado']) ?>">
      </div>

      <div class="form-group">
        <label>CEP</label>
        <input type="text" name="cep" value="<?= htmlspecialchars($loja['cep']) ?>">
      </div>

      <div class="form-group">
        <label>Telefone fixo</label>
        <input type="text" name="telefone_fixo" value="<?= htmlspecialchars($loja['telefone_fixo']) ?>">
      </div>

      <div class="form-group">
        <label>Celular</label>
        <input type="text" name="celular" value="<?= htmlspecialchars($loja['celular']) ?>">
      </div>

      <div class="form-group">
        <label>Gmail</label>
        <input type="email" name="email_gmail" value="<?= htmlspecialchars($loja['email_gmail']) ?>">
      </div>

      <div class="form-group">
        <label>Corporativo</label>
        <input type="email" name="email_corporativo" value="<?= htmlspecialchars($loja['email_corporativo']) ?>">
      </div>

      <div class="form-group">
        <label>Horário de funcionamento</label>
        <input type="text" name="dias_funcionamento" value="<?= htmlspecialchars($loja['dias_funcionamento']) ?>">
      </div>

      <div class="form-group">
        <label>Observações</label>
        <textarea name="observacoes" rows="3"><?= htmlspecialchars($loja['observacoes'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label>Gerente</label>
        <select name="gerente_id">
          <option value="0">—</option>
          <?php foreach ($lista as $f): ?>
            <?php $selected = ($loja['gerente_id'] ?? 0) == $f['id'] ? 'selected' : ''; ?>
            <option value="<?= $f['id'] ?>" <?= $selected ?>>
              <?= htmlspecialchars(labelFuncionario($f)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Subgerente</label>
        <select name="subgerente_id">
          <option value="0">—</option>
          <?php foreach ($lista as $f): ?>
            <?php $selected = ($loja['subgerente_id'] ?? 0) == $f['id'] ? 'selected' : ''; ?>
            <option value="<?= $f['id'] ?>" <?= $selected ?>>
              <?= htmlspecialchars(labelFuncionario($f)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn-primary">💾 Salvar</button>
        <a class="btn-secondary" href="loja.php?id=<?= $lojaId ?>">🔙 Cancelar</a>
      </div>

    </form>
  </div>

</body>
</html>
