<?php
session_start();
require_once '../dados/conexao.php';

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
  WHERE f.desligamento IS NULL
  ORDER BY f.nome
");

// $stmt->bind_param("i", $lojaId);
$stmt->execute();
$lista = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Atualiza dados da loja
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome              = trim($_POST['nome'] ?? '');
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

  $gerenteId = isset($_POST['gerente_id']) && $_POST['gerente_id'] !== '0' ? intval($_POST['gerente_id']) : null;
  $subgerenteId = isset($_POST['subgerente_id']) && $_POST['subgerente_id'] !== '0' ? intval($_POST['subgerente_id']) : null;

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

// Helper para montar label “Nome (Cargo) 📞 Telefone”
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
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body { font-family: Arial, sans-serif; }
    form label { display:block; margin-top:10px; font-weight:bold; }
    form input, form textarea, form select { width: 100%; max-width: 520px; padding:8px; box-sizing:border-box; }
    .acoes { margin-top: 16px; display:flex; gap:10px; }
    .btn { display:inline-block; padding:8px 14px; background:#007bff; color:#fff; border-radius:4px; text-decoration:none; border:none; cursor:pointer; }
    .btn:hover { background:#0056b3; }
    .btn-sec { background:#6c757d; }
    .btn-sec:hover { background:#5a6268; }
  </style>
</head>
<body>
  <h2>✏️ Editar Informações Gerais — <?= htmlspecialchars($loja['nome']) ?></h2>

  <form method="post">
    <label>Nome da unidade</label>
    <input type="text" name="nome" value="<?= htmlspecialchars($loja['nome']) ?>" required>

    <label>CNPJ</label>
    <input type="text" name="cnpj" value="<?= htmlspecialchars($loja['cnpj']) ?>">

    <label>Inscrição Estadual</label>
    <input type="text" name="inscricao_estadual" value="<?= htmlspecialchars($loja['inscricao_estadual'] ?? '') ?>">

    <label>Endereço</label>
    <input type="text" name="endereco" value="<?= htmlspecialchars($loja['endereco']) ?>">

    <label>Bairro</label>
    <input type="text" name="bairro" value="<?= htmlspecialchars($loja['bairro']) ?>">

    <label>Cidade</label>
    <input type="text" name="cidade" value="<?= htmlspecialchars($loja['cidade']) ?>">

    <label>Estado</label>
    <input type="text" name="estado" value="<?= htmlspecialchars($loja['estado']) ?>">

    <label>CEP</label>
    <input type="text" name="cep" value="<?= htmlspecialchars($loja['cep']) ?>">

    <label>Telefone fixo</label>
    <input type="text" name="telefone_fixo" value="<?= htmlspecialchars($loja['telefone_fixo']) ?>">

    <label>Celular</label>
    <input type="text" name="celular" value="<?= htmlspecialchars($loja['celular']) ?>">

    <label>Gmail</label>
    <input type="email" name="email_gmail" value="<?= htmlspecialchars($loja['email_gmail']) ?>">

    <label>Corporativo</label>
    <input type="email" name="email_corporativo" value="<?= htmlspecialchars($loja['email_corporativo']) ?>">

    <label>Horário de funcionamento</label>
    <input type="text" name="dias_funcionamento" value="<?= htmlspecialchars($loja['dias_funcionamento']) ?>">

    <label>Observações</label>
    <textarea name="observacoes" rows="3"><?= htmlspecialchars($loja['observacoes'] ?? '') ?></textarea>

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

    <div class="acoes">
      <button type="submit" class="btn">💾 Salvar</button>
      <a class="btn btn-sec" href="loja.php?id=<?= $lojaId ?>">🔙 Cancelar</a>
    </div>
  </form>
</body>
</html>
