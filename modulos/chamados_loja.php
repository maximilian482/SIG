<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Chamados TI</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php
$chamados = json_decode(@file_get_contents('../dados/chamados.json'), true);
$chamados = is_array($chamados) ? $chamados : [];

$setorAtual = 'Loja';
$abertos = [];
$fechados = [];

$c['loja_origem'] === $_SESSION['loja'];

$usuario = $_SESSION['usuario'];
$cargo   = $_SESSION['cargo'] ?? '';
$loja    = $_SESSION['loja'] ?? '';

$usuarioPodeTratar = (
  $c['usuario_solicitante'] === $usuario || 
  strtolower($cargo) === 'gerente'
);

foreach ($chamados as $i => $c) {
  if (($c['setor'] ?? '') !== $setorAtual) continue;
  if (($c['status'] ?? '') === 'Aberto') {
    $abertos[] = ['i' => $i, 'c' => $c];
  } elseif (($c['status'] ?? '') === 'Fechado') {
    $fechados[] = ['i' => $i, 'c' => $c];
  }
}
?>

<h2>🏬 Chamados Loja</h2>
<p>Visualize e gerencie chamados abertos para a Loja.</p>

<h3>Chamados em aberto</h3>
<table>
  <tr>
    <th>Loja</th>
    <th>Destino</th>
    <th>Descrição</th>
    <th>Abertura</th>
    <th>Tempo aberto</th>
    <th>Ações</th>
  </tr>
  <?php if (count($abertos) === 0): ?>
    <tr><td colspan="6" style="text-align:center;">Nenhum chamado aberto para TI.</td></tr>
  <?php else: ?>
    <?php foreach ($abertos as $row): ?>
      <?php
        $i = $row['i'];
        $c = $row['c'];
        $aberturaTs = strtotime($c['abertura'] ?? '');
        $tempoAberto = '—';
        if ($aberturaTs) {
          $diff = time() - $aberturaTs;
          $dias = floor($diff / 86400);
          $horas = floor(($diff % 86400) / 3600);
          $min = floor(($diff % 3600) / 60);
          $tempoAberto = $dias > 0 ? "{$dias}d {$horas}h" : ($horas > 0 ? "{$horas}h {$min}m" : "{$min}m");
        }
      ?>
      <tr>
        <td><?= htmlspecialchars($c['loja'] ?? '') ?></td>
        <td><?= htmlspecialchars($c['destino'] ?? '') ?></td>
        <td><?= htmlspecialchars($c['descricao'] ?? '') ?></td>
        <td><?= htmlspecialchars($c['abertura'] ?? '') ?></td>
        <td><?= htmlspecialchars($tempoAberto) ?></td>
        <td>
          <button onclick="abrirModalFecharChamado(<?= $i ?>)">✅ Fechar</button>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php endif; ?>
</table>

<h3 style="margin-top:30px;">Chamados encerrados</h3>
<table>
  <tr>
    <th>Loja</th>
    <th>Destino</th>
    <th>Descrição</th>
    <th>Abertura</th>
    <th>Fechamento</th>
    <th>Solução</th>
  </tr>
  <?php if (count($fechados) === 0): ?>
    <tr><td colspan="6" style="text-align:center;">Nenhum chamado encerrado para TI.</td></tr>
  <?php else: ?>
    <?php foreach ($fechados as $row): ?>
      <?php $c = $row['c']; ?>
      <tr>
        <td><?= htmlspecialchars($c['loja'] ?? '') ?></td>
        <td><?= htmlspecialchars($c['destino'] ?? '') ?></td>
        <td><?= htmlspecialchars($c['descricao'] ?? '') ?></td>
        <td><?= htmlspecialchars($c['abertura'] ?? '') ?></td>
        <td><?= htmlspecialchars($c['fechamento'] ?? '') ?></td>
        <td><?= htmlspecialchars($c['solucao'] ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
  <?php endif; ?>
</table>

<!-- Modal de fechamento -->
<div id="modalFecharChamado" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999;">
  <div style="background:#fff; margin:5% auto; padding:20px; width:500px; border-radius:8px;">
    <h3>✅ Fechar chamado</h3>
    <form method="POST" action="salvar_fechamento_chamado.php">
      <input type="hidden" name="id" id="fecharChamadoId">
      <label><strong>Solução aplicada:</strong></label><br>
      <textarea name="solucao" id="fecharChamadoSolucao" rows="4" required style="width:100%;"></textarea><br><br>
      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" onclick="fecharModalFecharChamado()">Cancelar</button>
        <button type="submit">Confirmar fechamento</button>
      </div>
    </form>
  </div>
</div>

<a class="btn" href="../index.php" style="margin-top:20px; display:inline-block;">🔙 Voltar ao painel principal</a>

<script>
function abrirModalFecharChamado(id) {
  document.getElementById('fecharChamadoId').value = id;
  document.getElementById('fecharChamadoSolucao').value = '';
  document.getElementById('modalFecharChamado').style.display = 'block';
}
function fecharModalFecharChamado() {
  document.getElementById('modalFecharChamado').style.display = 'none';
}
</script>

</body>
</html>
