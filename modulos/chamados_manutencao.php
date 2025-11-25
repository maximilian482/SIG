<?php
session_start();
require_once '../dados/conexao.php';
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['usuario'])) {
  header('Location: ../login.php');
  exit;
}

$conn = conectar();

// Função para formatar datas
function fmt_data_br($value) {
  if (!$value) return '—';
  $formats = ['Y-m-d H:i:s','Y-m-d H:i','Y-m-d','d/m/Y H:i:s','d/m/Y H:i','d/m/Y'];
  foreach ($formats as $f) {
    $dt = DateTime::createFromFormat($f, $value);
    if ($dt instanceof DateTime) return $dt->format('d/m/Y H:i');
  }
  $ts = strtotime($value);
  return $ts ? date('d/m/Y H:i', $ts) : '—';
}

// Buscar chamados do setor Manutenção
$abertos = [];
$fechados = [];

$stmt = $conn->prepare("
  SELECT ch.*, l.nome AS loja_nome, f.nome AS solicitante_nome
  FROM chamados ch
  LEFT JOIN lojas l ON ch.loja_origem = l.id
  LEFT JOIN funcionarios f ON ch.solicitante_id = f.id
  WHERE LOWER(ch.setor_destino) = 'manutencao'
  ORDER BY ch.data_abertura DESC
");

$stmt->execute();
$result = $stmt->get_result();

while ($c = $result->fetch_assoc()) {
  $status = strtolower(trim($c['status'] ?? ''));
  if (in_array($status, ['aberto','em andamento','reaberto','aguardando avaliação'])) {
    $abertos[] = $c;
  } elseif (in_array($status, ['encerrado','cancelado','finalizado'])) {
    $fechados[] = $c;
  }
}

// Função tempo aberto
function tempoAbertoStr(?string $dataAbertura): string {
  if (!$dataAbertura) return '—';
  $ts = strtotime($dataAbertura);
  if (!$ts) return '—';
  $diff  = time() - $ts;
  $dias  = floor($diff / 86400);
  $horas = floor(($diff % 86400) / 3600);
  $min   = floor(($diff % 3600) / 60);
  return $dias > 0 ? "{$dias}d {$horas}h" : ($horas > 0 ? "{$horas}h {$min}m" : "{$min}m");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Chamados Manutenção</title>
  <link rel="stylesheet" href="../css/chamados_setores.css">
</head>
<body>

<h2>🔧 Chamados Manutenção</h2>
<p>Visualize e gerencie chamados abertos para o setor de Manutenção.</p>

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
    <tr><td colspan="7" style="text-align:center;">Nenhum chamado aberto</td></tr>
  <?php else: ?>
    <?php foreach ($abertos as $c): ?>
      <?php
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
        <td><?= htmlspecialchars($c['loja_nome'] ?? '') ?></td>
        <td><?= htmlspecialchars($c['solicitante_nome'] ?? '') ?></td>
        <td><?= htmlspecialchars($c['descricao'] ?? '') ?></td>
        <td><?= fmt_data_br($c['data_abertura'] ?? '') ?></td>
        <td><?= htmlspecialchars($tempoAberto) ?></td>
        <td>
          <?php if ($status === 'reaberto'): ?>
            <div style="background:#f8d7da; padding:8px; border-radius:6px; font-size:14px; color:#721c24;">
              ❌ Reaberto pelo solicitante<br>
              <?php if (!empty($c['justificativa'])): ?>
                <em><?= nl2br(htmlspecialchars($c['justificativa'])) ?></em>
              <?php endif; ?>
            </div>
          <?php elseif ($status === 'aguardando avaliação'): ?>
            <div style="background:#fff3cd; padding:8px; border-radius:6px; font-size:14px; color:#856404;">
              📝 Aguardando avaliação do solicitante
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

<a class="btn" href="chamados_encerrados_setores.php?setor=manutencao" style="margin-bottom:20px;">📁 Ver encerrados</a>
<a class="btn" href="../index.php" style="margin-top:20px; display:inline-block;">🔙 Voltar ao painel</a>

<!-- Modal de fechamento -->
<div id="modalFecharChamado" class="modal">
  <div>
    <h3>✅ Fechar chamado</h3>
    <form id="formFecharChamado" onsubmit="enviarFechamentoChamado(event)">
      <input type="hidden" name="id" id="fecharChamadoId">
      <label><strong>Solução aplicada:</strong></label><br>
      <textarea name="solucao" id="fecharChamadoSolucao" rows="4" required></textarea><br><br>
      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" onclick="fecharModalFecharChamado()">Cancelar</button>
        <button type="submit">Confirmar fechamento</button>
      </div>
    </form>
  </div>
</div>

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
