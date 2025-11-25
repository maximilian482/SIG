<?php
require_once 'dados/conexao.php';
$conn = conectar();
session_start();

$emailDigitado = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $emailDigitado = strtolower(trim($_POST['email'] ?? ''));
  $senhaDigitada = trim($_POST['senha'] ?? '');

  $stmt = $conn->prepare("
    SELECT f.id, f.nome, f.cpf, f.email, f.senha, c.nome_cargo, f.loja_id, f.desligamento
    FROM funcionarios f
    JOIN cargos c ON f.cargo_id = c.id
    WHERE LOWER(TRIM(f.email)) = ?
    LIMIT 1
  ");

  $stmt->bind_param("s", $emailDigitado);
  $stmt->execute();
  $resultado = $stmt->get_result();

  if ($resultado->num_rows === 1) {
    $f = $resultado->fetch_assoc();
    $desligado = !empty($f['desligamento']) && $f['desligamento'] !== '0000-00-00';

    if (password_verify($senhaDigitada, $f['senha']) && !$desligado) {
      // Dados principais na sessão
      $_SESSION['usuario']        = $f['nome'] ?? '';
      $_SESSION['nome']           = $f['nome'] ?? '';
      $_SESSION['cpf']            = preg_replace('/\D+/', '', $f['cpf'] ?? '');
      $_SESSION['cargo']          = strtolower($f['nome_cargo'] ?? '');
      $_SESSION['loja']           = $f['loja_id'] ?? '';

      // Compatibilidade: salvar com os dois nomes
      $_SESSION['funcionario_id'] = $f['id']; // novo padrão
      $_SESSION['id_funcionario'] = $f['id']; // legado

      header('Location: index.php');
      exit;
    }
  }

  $erro = "Email ou senha inválidos, ou funcionário inativo.";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Login - Souza Farma Expressss</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/login.css">
</head>
<body>
  <div class="container">
    <div class="login-box">
      <img src="imagens/logo.png" alt="Logo Souza Farma Express" class="logo">
      <h2>🔐 Login</h2>

      <form method="POST">
        <label for="email">Email:</label>
        <input type="email" name="email" id="email"
               value="<?= htmlspecialchars($emailDigitado) ?>"
               required>

        <label for="senha">Senha:</label>
        <input type="password" name="senha" id="senha" required>

        <button type="submit">Entrar</button>
      </form>

      <?php if (!empty($erro)): ?>
        <p class="erro"><?= htmlspecialchars($erro) ?></p>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
