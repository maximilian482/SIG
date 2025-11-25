<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Chamados - Administração</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php
$chamados = json_decode(@file_get_contents('../dados/chamados.json'), true);
$chamados = is_array($chamados) ? $chamados : [];

$filtroSetor = $_GET['setor'] ?? '';
$filtroStatus = $_GET['status'] ?? '';
?>

<h2>📊 Painel de Chamados</h2>
<p>Visualize todos os chamados com filtros por setor e status.</p>

<form method="GET" style="margin-bottom:20px; display:flex; gap:20px;">
  <div>
    <label><strong>Setor:</strong></label>
    <select name="setor" onchange="this.form.submit()">
      <option value="">— Todos —</option>
      <option value="Tecnologia" <?= $filtroSetor === 'Tecnologia' ? 'selected' : '' ?>>Tecnologia</option>
      <option value="Manutencao" <?= $filtroSetor === 'Manutencao' ? 'selected' : '' ?>>Manutenção</option>
      <option value="Loja" <?= $filtroSetor === 'Loja' ? 'selected' : '' ?>>Loja</option>
    </select>
  </div>

  <div>
    <label><strong>Status:</strong></label>
    <select name="status" onchange="this.form.submit()">
      <option value="">— Todos —</option>
      <option value="Aberto" <?= $filtroStatus === 'Aberto' ? 'selected' : '' ?>>Aberto</option>
      <option value="Fechado" <?= $filtroStatus === 'Fechado' ? 'selected' : '' ?>>Fechado</option>
    </select>
  </div>
</form>

<table>
  <tr>
    <th>Loja</th>
    <th>Setor</th>
    <th>Destino</th>
    <th>Descrição</th>
    <th>Abertura</th>
    <th>Status</th>
    <th>Fechamento</th>
    <th>Solução</th>
  </tr>
  <?php
  foreach ($chamados as $c) {
    if ($filtroSetor && ($c['setor'] ?? '') !== $filtroSetor) continue;
    if ($filtroStatus && ($c['status'] ?? '') !== $filtroStatus) continue;

    echo "<tr>
      <td>" . htmlspecialchars($c['loja'] ?? '') . "</td>
      <td>" . htmlspecialchars($c['setor'] ?? '') . "</td>
      <td>" . htmlspecialchars($c['destino'] ?? '') . "</td>
      <td>" . htmlspecialchars($c['descricao'] ?? '') . "</td>
      <td>" . htmlspecialchars($c['abertura'] ?? '') . "</td>
      <td>" . htmlspecialchars($c['status'] ?? '') . "</td>
      <td>" . htmlspecialchars($c['fechamento'] ?? '—') . "</td>
      <td>" . htmlspecialchars($c['solucao'] ?? '—') . "</td>
    </tr>";
  }
  ?>
</table>

<a class="btn" href="../index.php" style="margin-top:20px;">🔙 Voltar ao painel</a>

</body>
</html>
