<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Adicionar Loja</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php
require_once '../dados/conexao.php';

$erro = '';
$sucesso = '';

// Carregar funcionários ativos
$funcionarios = [];
$resFunc = $conn->query("SELECT id, nome FROM funcionarios WHERE desligamento IS NULL ORDER BY nome");
while ($row = $resFunc->fetch_assoc()) {
  $funcionarios[$row['id']] = $row['nome'];
}

// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome              = trim($_POST['nome'] ?? '');
  $codigo_loja       = trim($_POST['codigo_loja'] ?? '');
  $endereco          = trim($_POST['endereco'] ?? '');
  $cidade            = trim($_POST['cidade'] ?? '');
  $bairro            = trim($_POST['bairro'] ?? '');
  $estado            = trim($_POST['estado'] ?? '');
  $ativo             = 1;
  $cnpj              = preg_replace('/\D/', '', $_POST['cnpj'] ?? '');
  $telefone_fixo     = trim($_POST['telefone_fixo'] ?? '');
  $celular           = trim($_POST['celular'] ?? '');
  $email_gmail       = trim($_POST['email_gmail'] ?? '');
  $email_corporativo = trim($_POST['email_corporativo'] ?? '');
  $gerente_id    = isset($_POST['gerente_id']) && $_POST['gerente_id'] !== '' ? intval($_POST['gerente_id']) : null;
  $subgerente_id = isset($_POST['subgerente_id']) && $_POST['subgerente_id'] !== '' ? intval($_POST['subgerente_id']) : null;
  $dias_funcionamento= trim($_POST['dias_funcionamento'] ?? '');
  $observacoes       = trim($_POST['observacoes'] ?? '');
  $cep               = preg_replace('/\D/', '', $_POST['cep'] ?? '');
  $inscricao_estadual= trim($_POST['inscricao_estadual'] ?? '');

  // Verifica CNPJ duplicado
  $verifica = $conn->prepare("SELECT id FROM lojas WHERE cnpj = ?");
  $verifica->bind_param("s", $cnpj);
  $verifica->execute();
  $verifica->store_result();

  if ($verifica->num_rows > 0) {
    $erro = '❌ Este CNPJ já está cadastrado.';
  } elseif ($gerente_id === $subgerente_id) {
    $erro = '❌ Gerente e subgerente não podem ser a mesma pessoa.';
  } elseif ($nome && $codigo_loja && $cnpj) {
    $stmt = $conn->prepare("
   INSERT INTO lojas (
      nome, codigo_loja, endereco, cidade, bairro, estado, ativo, cnpj,
      telefone_fixo, celular, email_gmail, email_corporativo,
      gerente_id, subgerente_id, dias_funcionamento, observacoes,
      cep, inscricao_estadual
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");

  $stmt->bind_param(
    "ssssssisssssisssss",
    $nome, $codigo_loja, $endereco, $cidade, $bairro, $estado, $ativo, $cnpj,
    $telefone_fixo, $celular, $email_gmail, $email_corporativo,
    $gerente_id, $subgerente_id, $dias_funcionamento, $observacoes,
    $cep, $inscricao_estadual
  );

    if ($stmt->execute()) {
      $sucesso = '✅ Loja cadastrada com sucesso!';
    } else {
      $erro = 'Erro ao cadastrar loja: ' . $conn->error;
    }
  } else {
    $erro = 'Preencha todos os campos obrigatórios.';
  }
}
?>

<h2>➕ Adicionar nova loja</h2>

<?php if ($erro): ?>
  <p style="color:red; font-weight:bold;"><?= $erro ?></p>
<?php elseif ($sucesso): ?>
  <p style="color:green; font-weight:bold;"><?= $sucesso ?></p>
<?php endif; ?>

<form method="POST" style="max-width:700px;">
  <label>Nome da unidade:</label><br>
  <input type="text" name="nome" required><br><br>

  <label>Código da loja:</label><br>
  <input type="text" name="codigo_loja" required><br><br>

  <label>Endereço:</label><br>
  <input type="text" name="endereco"><br><br>

  <label>Bairro:</label><br>
  <input type="text" name="bairro"><br><br>

  <label>Cidade:</label><br>
  <input type="text" name="cidade"><br><br>

  <label>Estado:</label><br>
  <input type="text" name="estado"><br><br>

  <label>CEP:</label><br>
  <input type="text" name="cep" maxlength="9" placeholder="00000-000"><br><br>

  <label>CNPJ:</label><br>
  <input type="text" name="cnpj" id="cnpj" required maxlength="18" placeholder="00.000.000/0000-00"><br><br>

  <label>Inscrição Estadual:</label><br>
  <input type="text" name="inscricao_estadual"><br><br>

  <label>Telefone fixo:</label><br>
  <input type="text" name="telefone_fixo"><br><br>

  <label>Celular:</label><br>
  <input type="text" name="celular"><br><br>

  <label>Email Gmail:</label><br>
  <input type="email" name="email_gmail"><br><br>

  <label>Email Corporativo:</label><br>
  <input type="email" name="email_corporativo"><br><br>

  <label>Gerente:</label><br>
  <select name="gerente_id">
    <option value="" disabled selected>Selecione</option>
    <?php foreach ($funcionarios as $id => $nome): ?>
      <option value="<?= $id ?>"><?= htmlspecialchars($nome) ?></option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Subgerente:</label><br>
  <select name="subgerente_id">
    <option value="" disabled selected>Selecione</option>
    <?php foreach ($funcionarios as $id => $nome): ?>
      <option value="<?= $id ?>"><?= htmlspecialchars($nome) ?></option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Dias de funcionamento:</label><br>
  <input type="text" name="dias_funcionamento" placeholder="Ex: Segunda a Sábado"><br><br>

  <!-- <label>Observações:</label><br>
  <textarea name="observacoes" rows="4" style="width:100%;"></textarea><br><br> -->

  <button type="submit" class="btn-filtro">💾 Cadastrar Loja</button>
  <a class="btn-filtro" href="lojas.php" style="margin-left:10px;">❌ Cancelar</a>
</form>

<script>
document.getElementById('cnpj').addEventListener('input', function(e) {
  let v = e.target.value.replace(/\D/g, '');
  if (v.length > 14) v = v.slice(0, 14);
  v = v.replace(/^(\d{2})(\d)/, '$1.$2');
  v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
  v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
  v = v.replace(/(\d{4})(\d)/, '$1-$2');
  e.target.value = v;
});
</script>

</body>
</html>
