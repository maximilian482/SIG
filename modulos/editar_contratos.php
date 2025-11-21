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

$contratos = $loja['contratos'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $novos = [];
  if (!empty($_POST['tipo'])) {
    foreach ($_POST['tipo'] as $i => $tipo) {
      if (trim($tipo)) {
        $novos[] = [
          'tipo'        => $tipo,
          'empresa'     => $_POST['empresa'][$i] ?? '',
          'telefone'    => $_POST['telefone'][$i] ?? '',
          'responsavel' => $_POST['responsavel'][$i] ?? '',
          'numero'      => $_POST['numero'][$i] ?? ''
        ];
      }
    }
  }
  $loja['contratos'] = $novos;
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
  <title>Editar Contratos</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body { font-family: Arial, sans-serif; }
    .contrato { margin-bottom: 20px; padding: 10px; background: #f9f9f9; border-radius: 6px; }
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
  <h2>📄 Editar Contratos — <?= htmlspecialchars($loja['nome'] ?? $nomeLoja) ?></h2>

  <form method="post">
    <?php foreach ($contratos as $i => $c): ?>
      <div class="contrato">
        <label>Tipo</label>
        <input type="text" name="tipo[]" value="<?= htmlspecialchars($c['tipo'] ?? '') ?>">

        <label>Empresa</label>
        <input type="text" name="empresa[]" value="<?= htmlspecialchars($c['empresa'] ?? '') ?>">

        <label>Telefone</label>
        <input type="text" name="telefone[]" value="<?= htmlspecialchars($c['telefone'] ?? '') ?>">

        <label>Responsável</label>
        <input type="text" name="responsavel[]" value="<?= htmlspecialchars($c['responsavel'] ?? '') ?>">

        <label>Nº do Contrato</label>
        <input type="text" name="numero[]" value="<?= htmlspecialchars($c['numero'] ?? '') ?>">
      </div>
    <?php endforeach; ?>

    <div id="novos"></div>
    <button type="button" class="btn" onclick="adicionarContrato()">➕ Adicionar contrato</button>

    <div class="acoes">
      <button type="submit" class="btn">💾 Salvar</button>
      <a href="loja.php?nome=<?= urlencode($nomeLoja) ?>" class="btn btn-sec">🔙 Cancelar</a>
    </div>
  </form>

  <script>
    function adicionarContrato() {
      const div = document.createElement('div');
      div.className = 'contrato';
      div.innerHTML = `
        <label>Tipo</label><input type="text" name="tipo[]" placeholder="Ex: Internet">
        <label>Empresa</label><input type="text" name="empresa[]" placeholder="Ex: Vivo">
        <label>Telefone</label><input type="text" name="telefone[]" placeholder="Ex: (11) 99999-0000">
        <label>Responsável</label><input type="text" name="responsavel[]" placeholder="Ex: João da Silva">
        <label>Nº do Contrato</label><input type="text" name="numero[]" placeholder="Ex: 2025-001">
      `;
      document.getElementById('novos').appendChild(div);
    }
  </script>
</body>
</html>
