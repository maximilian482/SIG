<?php
session_start();
if ($_SESSION['perfil'] !== 'admin') {
  header('Location: ../index.php');
  exit;
}

$funcionarios = json_decode(@file_get_contents('../dados/funcionarios.json'), true);
$funcionarios = is_array($funcionarios) ? $funcionarios : [];


$cargos = json_decode(@file_get_contents('../dados/cargos.json'), true) ?: [];
$lojas  = json_decode(@file_get_contents('../dados/gerencial.json'), true) ?: [];

$erro     = $_GET['erro'] ?? '';
$sucesso  = $_GET['sucesso'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Cadastrar Usuário</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<h2>🔐 Cadastrar Usuário</h2>

<?php if ($erro): ?>
  <div style="background:#ffe0e0; color:#a00; padding:10px; border:1px solid #a00; margin-bottom:20px;">
    ⚠️ <?= htmlspecialchars($erro) ?>
  </div>
<?php endif; ?>

<?php if ($sucesso): ?>
  <div style="background:#e0ffe0; color:#080; padding:10px; border:1px solid #080; margin-bottom:20px;">
    ✅ <?= htmlspecialchars($sucesso) ?>
  </div>
<?php endif; ?>

<form method="POST" action="salvar_vinculo_usuario.php" style="max-width:600px;">
  
  <label><strong>Nome do funcionário:</strong></label><br>
    <select name="nome" id="nome" onchange="preencherCpfCargoLoja()" required>
      <option value="">— Selecione —</option>
      <?php foreach ($funcionarios as $loja => $lista): ?>
        <?php foreach ($lista as $f): ?>
          <?php if (!empty($f['nome']) && !empty($f['ativo'])): ?>
            <option value="<?= htmlspecialchars($f['nome']) ?>"
                    data-cpf="<?= htmlspecialchars($f['cpf']) ?>"
                    data-cargo="<?= htmlspecialchars($f['cargo']) ?>"
                    data-loja="<?= htmlspecialchars($loja) ?>">
              <?= htmlspecialchars($f['nome']) ?>
            </option>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </select><br><br>

<label><strong>CPF:</strong></label><br>
<input type="text" name="cpf" id="cpf" readonly><br><br>


  <label><strong>Usuário (login):</strong></label><br>
  <input type="text" name="usuario" required><br><br>

  <label><strong>Senha:</strong></label><br>
  <input type="password" name="senha" required><br><br>

 <!-- <label><strong>Cargo:</strong></label><br>
  <select name="cargo" id="cargo" required>
    <option value="">— Selecione —</option>
    <?php foreach ($cargos as $cargo): ?>
      <option value="<?= htmlspecialchars($cargo) ?>"><?= htmlspecialchars($cargo) ?></option>
    <?php endforeach; ?>
  </select><br><br>

  <label><strong>Loja:</strong></label><br>
  <select name="loja" id="loja">
    <option value="">— Nenhuma —</option>
    <?php foreach ($lojas as $id => $dados): ?>
      <option value="<?= htmlspecialchars($id) ?>">
        <?= htmlspecialchars($dados['nome'] ?? $id) ?>
      </option>
    <?php endforeach; ?>
  </select><br><br> -->

    <label><strong>Cargo:</strong></label><br>
    <input type="text" name="cargo" id="cargo" readonly><br><br>

    <label><strong>Loja:</strong></label><br>
    <input type="text" name="loja" id="loja" readonly><br><br>

  <label><strong>Perfil de acesso:</strong></label><br>
  <select name="perfil" required>
    <option value="padrao">Padrão</option>
    <option value="admin">Administrador</option>
  </select><br><br>

  <button type="submit">✅ Cadastrar usuário</button>
  <a class="btn" href="listar_usuarios.php" style="margin-left:10px;">📋 Listar usuários</a>
  <a class="btn" href="../index.php" style="margin-left:10px;">🔙 Voltar</a>

</form>

<script>
  function preencherCpfCargoLoja() {
    const select = document.getElementById('nome');
    const selected = select.options[select.selectedIndex];

    document.getElementById('cpf').value   = selected.getAttribute('data-cpf') || '';
    document.getElementById('cargo').value = selected.getAttribute('data-cargo') || '';
    document.getElementById('loja').value  = selected.getAttribute('data-loja') || '';
  }
</script>

</body>
</html>
