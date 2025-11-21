<?php
session_start();
if (!isset($_SESSION['usuario']) || ($_SESSION['perfil'] ?? '') !== 'admin') {
  header('Location: ../index.php');
  exit;
}

$nomeLoja = $_GET['nome'] ?? '';
$dados = json_decode(@file_get_contents('../dados/gerencial.json'), true) ?: [];
$loja = $dados[$nomeLoja] ?? null;

if (!$loja) {
  echo "<p>❌ Loja não encontrada.</p>";
  exit;
}

$documentos = $loja['documentos'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $novos = [];
  if (!empty($_POST['nome'])) {
    foreach ($_POST['nome'] as $i => $nome) {
      if (trim($nome)) {
        $arquivo = $_POST['arquivo_nome'][$i] ?? '';

        // Simulação de upload (ainda não funcional)
        if (!empty($_FILES['arquivo_upload']['name'][$i])) {
          $arquivo = 'documentos/' . basename($_FILES['arquivo_upload']['name'][$i]);
          // Upload real será implementado com backend
        }

        $novos[] = [
          'nome'     => $nome,
          'validade' => $_POST['validade'][$i] ?? '',
          'arquivo'  => $arquivo
        ];
      }
    }
  }

  $loja['documentos'] = $novos;
  $dados[$nomeLoja] = $loja;
  file_put_contents('../dados/gerencial.json', json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
  header("Location: loja.php?nome=" . urlencode($nomeLoja));
  exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Editar Documentos da Loja</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body { font-family: Arial, sans-serif; }
    .documento { margin-bottom: 20px; padding: 10px; background: #f9f9f9; border-radius: 6px; }
    label { display:block; margin-top:10px; font-weight:bold; }
    input { width: 100%; max-width: 500px; padding:8px; box-sizing:border-box; }
    .acoes { margin-top: 20px; display:flex; gap:10px; }
    .btn { padding:8px 14px; background:#007bff; color:#fff; border:none; border-radius:4px; cursor:pointer; text-decoration:none; }
    .btn:hover { background:#0056b3; }
    .btn-sec { background:#6c757d; }
    .btn-sec:hover { background:#5a6268; }
  </style>
</head>
<body>
  <h2>📁 Editar Documentos — <?= htmlspecialchars($loja['nome'] ?? $nomeLoja) ?></h2>

  <form method="post" enctype="multipart/form-data">
    <?php foreach ($documentos as $i => $doc): ?>
      <div class="documento">
        <label>Nome do documento</label>
        <input type="text" name="nome[]" value="<?= htmlspecialchars($doc['nome'] ?? '') ?>">

        <label>Data do documento</label>
        <input type="date" name="validade[]" value="<?= htmlspecialchars($doc['validade'] ?? '') ?>">

        <label>Arquivo atual (editável)</label>
        <input type="text" name="arquivo_nome[]" value="<?= htmlspecialchars($doc['arquivo'] ?? '') ?>" placeholder="Ex: documentos/contrato_lojaA.pdf">

        <label>📤 Enviar novo arquivo</label>
        <input type="file" name="arquivo_upload[]">
      </div>
    <?php endforeach; ?>

    <div id="novos"></div>
    <button type="button" class="btn" onclick="adicionarDocumento()">➕ Adicionar documento</button>

    <div class="acoes">
      <button type="submit" class="btn">💾 Salvar</button>
      <a href="loja.php?nome=<?= urlencode($nomeLoja) ?>" class="btn btn-sec">🔙 Cancelar</a>
    </div>
  </form>

  <script>
    function adicionarDocumento() {
      const div = document.createElement('div');
      div.className = 'documento';
      div.innerHTML = `
        <label>Nome do documento</label><input type="text" name="nome[]" placeholder="Ex: Contrato de Aluguel">
        <label>Data do documento</label><input type="date" name="validade[]">
        <label>Nome do arquivo</label><input type="text" name="arquivo_nome[]" placeholder="Ex: documentos/contrato_lojaA.pdf">
        <label>📤 Enviar novo arquivo</label><input type="file" name="arquivo_upload[]">
      `;
      document.getElementById('novos').appendChild(div);
    }
  </script>
</body>
</html>
