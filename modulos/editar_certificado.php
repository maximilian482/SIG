<?php
session_start();
require_once '../dados/conexao.php';
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['usuario'])) {
  header('Location: ../login.php');
  exit;
}

$conn = conectar();

// ID da loja recebido pela URL
$lojaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($lojaId <= 0) {
  die("Loja não informada.");
}

// Buscar dados da loja (apenas leitura; não criamos/alteramos loja aqui)
$stmtLoja = $conn->prepare("SELECT id, nome FROM lojas WHERE id = ?");
$stmtLoja->bind_param("i", $lojaId);
$stmtLoja->execute();
$loja = $stmtLoja->get_result()->fetch_assoc();
if (!$loja) {
  die("Loja não encontrada.");
}

// Buscar certificado existente
$stmtCert = $conn->prepare("
  SELECT validade, arquivo, TRIM(COALESCE(senha, '')) AS senha
  FROM lojas_certificados
  WHERE loja_id = ?
  LIMIT 1
");
$stmtCert->bind_param("i", $lojaId);
$stmtCert->execute();
$certificado = $stmtCert->get_result()->fetch_assoc();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validade = $_POST['validade'] ?? null;
    $senha    = isset($_POST['senha']) ? trim($_POST['senha']) : null;

    // Mantém arquivo atual se não enviar novo
    $arquivo  = $certificado['arquivo'] ?? null;

    // Upload de arquivo (opcional)
    if (!empty($_FILES['arquivo']['name'])) {
        $pastaBase = "../uploads/certificados/";
        if (!is_dir($pastaBase)) {
            mkdir($pastaBase, 0777, true);
        }
        $nomeArquivo = uniqid("cert_") . "_" . basename($_FILES['arquivo']['name']);
        $caminhoRel  = "uploads/certificados/" . $nomeArquivo; // caminho relativo para armazenar no banco
        $caminhoAbs  = $pastaBase . $nomeArquivo;

        if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminhoAbs)) {
            $arquivo = $caminhoRel;
        }
    }

    // Decide entre UPDATE e INSERT sem tocar na tabela 'lojas'
    $conn->begin_transaction();

    try {
        // Verifica se já existe certificado para esta loja
        $stmtCheck = $conn->prepare("SELECT 1 FROM lojas_certificados WHERE loja_id = ? LIMIT 1");
        $stmtCheck->bind_param("i", $lojaId);
        $stmtCheck->execute();
        $existe = $stmtCheck->get_result()->fetch_column();

        if ($existe) {
            // UPDATE (não sobrescreve arquivo se não tiver novo)
            if ($arquivo !== null) {
                $stmtUpd = $conn->prepare("
                  UPDATE lojas_certificados
                     SET validade = ?, arquivo = ?, senha = ?
                   WHERE loja_id = ?
                ");
                $stmtUpd->bind_param("sssi", $validade, $arquivo, $senha, $lojaId);
            } else {
                $stmtUpd = $conn->prepare("
                  UPDATE lojas_certificados
                     SET validade = ?, senha = ?
                   WHERE loja_id = ?
                ");
                $stmtUpd->bind_param("ssi", $validade, $senha, $lojaId);
            }

            if (!$stmtUpd->execute()) {
                throw new Exception("Erro ao atualizar certificado: " . $stmtUpd->error);
            }
        } else {
            // INSERT (registro inicial do certificado)
            $stmtIns = $conn->prepare("
              INSERT INTO lojas_certificados (loja_id, validade, arquivo, senha)
              VALUES (?, ?, ?, ?)
            ");
            $stmtIns->bind_param("isss", $lojaId, $validade, $arquivo, $senha);
            if (!$stmtIns->execute()) {
                throw new Exception("Erro ao inserir certificado: " . $stmtIns->error);
            }
        }

        $conn->commit();
        header("Location: loja.php?id=" . urlencode($lojaId));
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        die($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Editar Certificado - <?= htmlspecialchars($loja['nome']) ?></title>
  <link rel="stylesheet" href="../css/chamados_setores.css">
</head>
<body>
  <h2>📑 Editar Certificado Digital - <?= htmlspecialchars($loja['nome']) ?></h2>

  <form method="post" enctype="multipart/form-data" style="max-width:500px;">
    <label><strong>Validade:</strong></label><br>
    <input type="date" name="validade" value="<?= htmlspecialchars($certificado['validade'] ?? '') ?>" required><br><br>

    <label><strong>Arquivo do certificado:</strong></label><br>
    <?php if (!empty($certificado['arquivo'])): ?>
      <p>Atual: <a href="../<?= htmlspecialchars($certificado['arquivo']) ?>" download>📥 Baixar atual</a></p>
    <?php endif; ?>
    <input type="file" name="arquivo" accept=".pfx,.pdf,.crt,.pem"><br><br>

    <label><strong>Senha do certificado:</strong></label><br>
    <div style="display:flex; gap:8px; align-items:center;">
      <input type="password" id="senhaInput" name="senha" value="<?= htmlspecialchars($certificado['senha'] ?? '') ?>" required style="flex:1;">
      <button type="button" onclick="toggleSenha()" style="cursor:pointer;">👁️</button>
    </div><br>

    <button type="submit">💾 Salvar</button>
    <a href="loja.php?id=<?= urlencode($lojaId) ?>" class="btn">🔙 Voltar</a>
  </form>

  <script>
    function toggleSenha() {
      const campo = document.getElementById('senhaInput');
      campo.type = (campo.type === 'password') ? 'text' : 'password';
    }
  </script>
</body>
</html>
