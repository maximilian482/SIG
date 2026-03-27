<?php
require_once 'dados/conexao.php';
$conn = conectar();
session_start();

$emailDigitado = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailDigitado = strtolower(trim($_POST['email'] ?? ''));
    $senhaDigitada = trim($_POST['senha'] ?? '');

    // Buscar usuário pelo email (normalizando para comparação)
    $sql = "
        SELECT 
            f.id, 
            f.nome, 
            f.cpf, 
            f.email, 
            f.senha, 
            f.id_setor,
            f.loja_id, 
            f.desligamento,
            f.cargo_id,
            c.nome_cargo
        FROM funcionarios f
        LEFT JOIN cargos c ON f.cargo_id = c.id
        WHERE LOWER(TRIM(f.email)) = ?
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        // erro no prepare — registrar e falhar com mensagem genérica
        error_log("LOGIN prepare error: " . $conn->error);
        $erro = "Erro no servidor. Tente novamente mais tarde.";
    } else {
        $stmt->bind_param("s", $emailDigitado);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado && $resultado->num_rows === 1) {
            $f = $resultado->fetch_assoc();
            $desligamento = trim((string)($f['desligamento'] ?? ''));
            $desligado = ($desligamento !== '' && $desligamento !== '0000-00-00');

            if (!$desligado) {
                $senhaDigitadaLimpa = trim($senhaDigitada);
                $senhaOk = false;

                // CPF sem máscara e 6 primeiros dígitos
                $cpfLimpo       = preg_replace('/\D+/', '', $f['cpf'] ?? '');
                $senhaPadraoCPF = substr($cpfLimpo, 0, 6);

                // 1) Senha já criptografada
                if (!empty($f['senha']) && password_verify($senhaDigitadaLimpa, $f['senha'])) {
                    $senhaOk = true;
                }
                // 2) Senha em texto puro (legado) — migrar para hash
                elseif (!empty($f['senha']) && trim($f['senha']) === $senhaDigitadaLimpa) {
                    $senhaOk = true;
                    $novoHash = password_hash($senhaDigitadaLimpa, PASSWORD_DEFAULT);
                    $stmtUpd = $conn->prepare("UPDATE funcionarios SET senha = ? WHERE id = ?");
                    if ($stmtUpd) {
                        $stmtUpd->bind_param("si", $novoHash, $f['id']);
                        $stmtUpd->execute();
                        $stmtUpd->close();
                    } else {
                        error_log("LOGIN update hash prepare error: " . $conn->error);
                    }
                }
                // 3) Senha padrão = 6 primeiros dígitos do CPF (quando senha no banco está vazia)
                elseif (empty($f['senha']) && $senhaDigitadaLimpa === $senhaPadraoCPF) {
                    $senhaOk = true;
                    $novoHash = password_hash($senhaPadraoCPF, PASSWORD_DEFAULT);
                    $stmtUpd = $conn->prepare("UPDATE funcionarios SET senha = ? WHERE id = ?");
                    if ($stmtUpd) {
                        $stmtUpd->bind_param("si", $novoHash, $f['id']);
                        $stmtUpd->execute();
                        $stmtUpd->close();
                    } else {
                        error_log("LOGIN update default hash prepare error: " . $conn->error);
                    }
                }

                if ($senhaOk) {
                    // Regenerar id da sessão por segurança
                    if (function_exists('session_regenerate_id')) {
                        session_regenerate_id(true);
                    }

                    // Normalizar e gravar valores essenciais na sessão (tipos corretos)
                    $cpfLimpoSess = preg_replace('/\D+/', '', (string)($f['cpf'] ?? ''));

                    // cargo normalizado (ex.: 'ceo', 'super', 'gerente', etc.)
                    $cargoRaw = trim((string)($f['nome_cargo'] ?? ''));
                    $cargoNorm = strtolower($cargoRaw);

                    $_SESSION['usuario']        = trim((string)($f['nome'] ?? ''));
                    $_SESSION['nome']           = trim((string)($f['nome'] ?? ''));
                    $_SESSION['cpf']            = $cpfLimpoSess;
                    $_SESSION['cargo']          = $cargoNorm;
                    $_SESSION['cargo_id']       = intval($f['cargo_id'] ?? 0);
                    $_SESSION['loja']           = intval($f['loja_id'] ?? 0);

                    $_SESSION['funcionario_id'] = intval($f['id']);
                    // manter compatibilidade com chaves antigas
                    $_SESSION['id_funcionario'] = intval($f['id']);
                    $_SESSION['id_setor']       = isset($f['id_setor']) ? intval($f['id_setor']) : null;

                    // flags úteis
                    $_SESSION['is_super_ou_ceo'] = in_array($cargoNorm, ['super', 'ceo'], true);

                    // fechar statement e redirecionar
                    $stmt->close();
                    header('Location: index.php');
                    exit;
                }
            }
        }

        // fechar statement se ainda aberto
        if ($stmt) $stmt->close();

        // mensagem genérica de erro (não detalhar se email ou senha)
        $erro = "Email ou senha inválidos, ou funcionário inativo.";
    }
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Login - Souza Farma Express</title>
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
