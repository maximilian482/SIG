<?php
session_start();
if ($_SESSION['perfil'] !== 'admin') {
  header('Location: ../index.php');
  exit;
}

$cpf = $_GET['cpf'] ?? '';
$vinculos = json_decode(@file_get_contents('../dados/vinculos_usuarios.json'), true);
$usuarios = json_decode(@file_get_contents('../dados/usuarios.json'), true);
$funcionarios = json_decode(@file_get_contents('../dados/funcionarios.json'), true);

if (!isset($vinculos[$cpf])) {
  echo "Vínculo não encontrado.";
  exit;
}

$v = $vinculos[$cpf];
$usuario = $v['usuario'];
$dadosUsuario = $usuarios[$usuario] ?? [];

$nome = $funcionarios[$cpf]['nome'] ?? '(desconhecido)';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Editar Acesso</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<h2>✏️ Editar Acesso</h2>
<p>Funcionário: <strong><?= htmlspecialchars($nome) ?></strong> (CPF: <?= htmlspecialchars($cpf) ?>)</p>

<form method="POST" action="salvar_edicao_usuario.php">
  <input type="hidden" name="cpf" value="<?= htmlspecialchars($cpf) ?>">

  <label><strong>Usuário de login:</strong></label><br>
  <input type="text" name="usuario" value="<?= htmlspecialchars($usuario) ?>" required><br><br>

  <label><strong>Nova senha:</strong></label><br>
  <input type="password" name="senha" placeholder="Digita sua senha" required><br><br>

  <label><strong>Perfil de acesso:</strong></label><br>
  <select name="perfil" required>
    <option value="padrao" <?= $v['perfil'] === 'padrao' ? 'selected' : '' ?>>Padrão</option>
    <option value="admin" <?= $v['perfil'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
  </select><br><br>

  <button type="submit">💾 Salvar alterações</button>
  <a class="btn" href="listar_usuarios.php">🔙 Voltar</a>
</form>

</body>
</html>
