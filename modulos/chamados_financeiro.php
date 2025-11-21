<?php
$chamados = json_decode(@file_get_contents('../dados/chamados.json'), true);
$chamados = is_array($chamados) ? $chamados : [];

$abertos = [];
$fechados = [];


function normalizar($texto) {
  $texto = strtolower(trim($texto));
  $texto = str_replace(['á','à','â','ã','ä'], 'a', $texto);
  $texto = str_replace(['é','è','ê','ë'], 'e', $texto);
  $texto = str_replace(['í','ì','î','ï'], 'i', $texto);
  $texto = str_replace(['ó','ò','ô','õ','ö'], 'o', $texto);
  $texto = str_replace(['ú','ù','û','ü'], 'u', $texto);
  $texto = str_replace(['ç'], 'c', $texto);
  return $texto;
}

$status = normalizar($c['status'] ?? '');
if (in_array($status, ['aberto', 'em andamento', 'reaberto', 'aguardando avaliacao'])) {
  $abertos[] = ['i' => $i, 'c' => $c];
}


foreach ($chamados as $i => $c) {
  $setor = strtolower(trim($c['setor_destino'] ?? ''));
  $status = strtolower(trim($c['status'] ?? ''));

  if (normalizar($c['setor_destino'] ?? '') !== 'financeiro') continue;


  if (in_array($status, ['aberto', 'em andamento', 'reaberto', 'aguardando avaliação'])) {
    $abertos[] = ['i' => $i, 'c' => $c];
  } elseif ($status === 'encerrado') {
    $fechados[] = ['i' => $i, 'c' => $c];
  }
}

if (!function_exists('fmt_data_br')) {
  function fmt_data_br($value) {
    if (!$value) return '—';
    $formats = ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d', 'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y'];
    foreach ($formats as $f) {
      $dt = DateTime::createFromFormat($f, $value);
      if ($dt instanceof DateTime) return $dt->format('d-m-Y');
    }
    $ts = strtotime($value);
    return $ts ? date('d-m-Y', $ts) : '—';
  }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Chamados Financeiro</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<h2>💰 Chamados Financeiro</h2>
<p>Visualize e gerencie chamados abertos para o seu setor</p>

<h3>Chamados Abertos</h3>
<table>
  <tr>
    <th>Loja</th>
    <th>Solicitante</th>
    <th>Descrição</th>
    <th>Abertura</th>
    <th>Tempo aberto</th>
    <th>Reabertura</th>
    <th>Ações</th>
  </tr>
  <?php if (count($abertos) === 0): ?>
    <tr><td colspan="7" style="text-align:center;">Nenhum chamado aberto.</td></tr>
  <?php else: ?>
    <?php foreach ($abertos as $row): ?>
      <?php
        $i = $row['i'];
        $c = $row['c'];
        $status = strtolower(trim($c['status'] ?? ''));
        $aberturaTs = strtotime($c['data_abertura'] ?? '');
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
        <td><?= htmlspecialchars($c['loja_origem'] ?? '') ?></td>
        <td><?= htmlspecialchars($c['usuario_solicitante'] ?? '') ?></td>
        <td><?= htmlspecialchars($c['descricao'] ?? '') ?></td>
        <td><?= fmt_data_br($c['data_abertura'] ?? '') ?></td>
        <td><?= htmlspecialchars($tempoAberto) ?></td>
        <td>
          <?php if (!empty($c['justificativa_solicitante'])): ?>
            <div style="background:#fff3cd; padding:8px; border-radius:6px; font-size:14px;">
              <?= nl2br(htmlspecialchars($c['justificativa_solicitante'])) ?>
            </div>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
        <td>
          <?php if ($status === 'aguardando avaliação'): ?>
            <div style="color:#2980b9; font-weight:bold;">📝 Aguardando avaliação</div>
          <?php else: ?>
            <button onclick="abrirModalFecharChamado('<?= $c['id'] ?>')">✅ Fechar</button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php endif; ?>
</table>

<!-- Modal de fechamento -->
<div id="modalFecharChamado" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999;">
  <div style="background:#fff; margin:5% auto; padding:20px; width:500px; border-radius:8px;">
    <h3>✅ Fechar chamado</h3>
    <form id="formFecharChamado" onsubmit="enviarFechamentoChamado(event)">
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

<a class="btn" href="chamados_encerrados_setores.php?setor=financeiro" style="margin-bottom:20px;">📁 Ver encerrados</a>
<a class="btn" href="../index.php" style="margin-top:20px; display:inline-block;">🔙 Voltar ao painel</a>

<script>
function abrirModalFecharChamado(id) {
  document.getElementById('fecharChamadoId').value = id;
  document.getElementById('fecharChamadoSolucao').value = '';
  document.getElementById('modalFecharChamado').style.display = 'block';
}
function fecharModalFecharChamado() {
  document.getElementById('modalFecharChamado').style.display = 'none';
}
function enviarFechamentoChamado(event) {
  event.preventDefault();

  const id = document.getElementById('fecharChamadoId').value;
  const solucao = document.getElementById('fecharChamadoSolucao').value;

  fetch('salvar_fechamento_chamado.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `id=${encodeURIComponent(id)}&solucao=${encodeURIComponent(solucao)}`
  })
  .then(response => response.text())
  .then(data => {
    fecharModalFecharChamado();
    alert(data);
    location.reload();
  })
  .catch(error => {
    alert('Erro ao fechar chamado.');
    console.error(error);
  });
}
</script>

</body>
</html>
