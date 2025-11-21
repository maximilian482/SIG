<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();


// Verifica login
if (!isset($_SESSION['usuario']) || !isset($_SESSION['cpf'])) {
  header('Location: ../login.php');
  exit;
}

$usuario     = $_SESSION['usuario'];
$cpf         = $_SESSION['cpf'];
$loja        = $_SESSION['loja'] ?? '';
$nomeUsuario = $_SESSION['nome'] ?? $usuario;

// Limpa CPF para garantir compatibilidade com o banco
$cpfLimpo = preg_replace('/\D+/', '', $cpf);

// Buscar ID do funcionário logado (corrigido)
$idSolicitante = 0;
$stmtUser = $conn->prepare("
  SELECT id FROM funcionarios
  WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?
");
$stmtUser->bind_param("s", $cpfLimpo);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
if ($resultUser->num_rows === 1) {
  $idSolicitante = $resultUser->fetch_assoc()['id'] ?? 0;
}

// Processar envio
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $titulo        = trim($_POST['titulo'] ?? '');
  $descricao     = trim($_POST['descricao'] ?? '');
  $setor_destino = trim($_POST['setor_destino'] ?? '');

  if ($titulo && $descricao && $setor_destino && $loja && $idSolicitante) {
    $stmt = $conn->prepare("
      INSERT INTO chamados (
        codigo_chamado, titulo, descricao, setor_destino, loja_origem,
        data_abertura, status, solicitante_id
      ) VALUES (?, ?, ?, ?, ?, NOW(), 'aberto', ?)
    ");

    $codigo = 'CHM-' . date('Ymd') . '-' . rand(100, 999);
    $stmt->bind_param("ssssii", $codigo, $titulo, $descricao, $setor_destino, $loja, $idSolicitante);

    if ($stmt->execute()) {
      header("Location: acompanhar_chamados_publico.php?sucesso=1");
      exit;
    } else {
      $erro = "Erro ao registrar chamado: " . $stmt->error;
    }
  } else {
    $erro = "❌ Preencha todos os campos obrigatórios.";
  }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Abrir Chamado</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == 1): ?>
  <p style="color:green; font-weight:bold;">✅ Chamado registrado com sucesso!</p>
<?php endif; ?>

<h2>➕ Abrir novo chamado</h2>
<p>Preencha os dados abaixo para registrar um chamado técnico ou administrativo.</p>

<?php if (!empty($erro)): ?>
  <p style="color:red;"><strong><?= htmlspecialchars($erro) ?></strong></p>
<?php endif; ?>

<form method="POST">
  <label>Título:</label><br>
  <input type="text" name="titulo" required style="width:100%;"><br><br>

  <label>Descrição:</label><br>
  <textarea name="descricao" rows="4" required style="width:100%;"></textarea><br><br>

  <label>Setor de destino:</label><br>
  <select name="setor_destino" required>
    <option value="">— Selecione —</option>
    <option value="TI">TI</option>
    <option value="Manutencao">Manutenção</option>
    <option value="Supervisao">Supervisão</option>
    <option value="Financeiro">Financeiro</option>
    <option value="RH">RH</option>
    <option value="Compras">Compras</option>
  </select><br><br>

  <button type="submit">📨 Enviar chamado</button>
</form>

<br>
<a class="btn" href="acompanhar_chamados_publico.php">🔙 Voltar</a>

</body>
</html>
