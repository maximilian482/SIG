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

$equipamentos = $loja['equipamentos'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $novos = [];
  if (!empty($_POST['nome'])) {
    foreach ($_POST['nome'] as $i => $nome) {
      if (trim($nome)) {
        $novos[] = [
          'nome' => $nome,
          'ip' => $_POST['ip'][$i] ?? '',
          'observacao' => $_POST['observacao'][$i] ?? ''
        ];
      }
    }
  }
  $loja['equipamentos'] = $novos;
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
  <title>Editar Equipamentos</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body { font-family: Arial, sans-serif; }
    .equipamento { margin-bottom: 20px; padding: 10px; background: #f9f9f9; border-radius: 6px; }
    label { display:block; margin-top:10px; font-weight:bold; }
    input, textarea { width: 100%; max-width: 500px; padding:8px; box-sizing:border-box; }
    .acoes { margin-top: 20px; display:flex; gap:10px; }
    .btn { padding:8px 14px; background:#007bff; color:#fff; border:none; border-radius:4px; cursor:pointer; text-decoration:none; }
    .btn:hover { background:#0056b3; }
    .btn-sec { background:#6c757d; }
    .btn-sec:hover { background:#5a6268; }
  </style>
</head>
<body>
  <h2>🖥️ Editar Equipamentos — <?= htmlspecialchars($loja['nome'] ?? $nomeLoja) ?></h2>

  <form method="post">
    <?php foreach ($equipamentos as $i => $eq): ?>
      <div class="equipamento">
        <label>Nome</label>
        <input type="text" name="nome[]" value="<?= htmlspecialchars($eq['nome'] ?? '') ?>">

        <label>IP</label>
        <input type="text" name="ip[]" value="<?= htmlspecialchars($eq['ip'] ?? '') ?>">

        <label>Observação</label>
        <textarea name="observacao[]" rows="2"><?= htmlspecialchars($eq['observacao'] ?? '') ?></textarea>
      </div>
    <?php endforeach; ?>

    <div id="novos"></div>
    <button type="button" class="btn" onclick="adicionarEquipamento()">➕ Adicionar equipamento</button>

    <div class="acoes">
      <button type="submit" class="btn">💾 Salvar</button>
      <a href="loja.php?nome=<?= urlencode($nomeLoja) ?>" class="btn btn-sec">🔙 Cancelar</a>
    </div>
  </form>

  <script>
    function adicionarEquipamento() {
      const div = document.createElement('div');
      div.className = 'equipamento';
      div.innerHTML = `
        <label>Nome</label><input type="text" name="nome[]" placeholder="Ex: Impressora X">
        <label>IP</label><input type="text" name="ip[]" placeholder="Ex: 192.168.0.10">
        <label>Observação</label><textarea name="observacao[]" rows="2" placeholder="Ex: Sem scanner"></textarea>
      `;
      document.getElementById('novos').appendChild(div);
    }
  </script>
</body>
</html>
