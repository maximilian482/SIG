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

// Descobre setor pela URL (?setor=ti, manutencao, rh...)
$setor = strtolower($_GET['setor'] ?? 'ti');

// Buscar chamados encerrados do setor escolhido
$stmt = $conn->prepare("
  SELECT c.id,
         l.nome AS loja_nome,
         f.nome AS solicitante_nome,
         c.descricao,
         c.solucao,
         r.nome AS responsavel_nome,
         c.avaliacao,
         c.justificativa,
         c.data_avaliacao
    FROM chamados c
    LEFT JOIN lojas l ON c.loja_origem = l.id
    LEFT JOIN funcionarios f ON c.solicitante_id = f.id
    LEFT JOIN funcionarios r ON c.responsavel_id = r.id
   WHERE LOWER(c.setor_destino) = ?
     AND LOWER(c.status) = 'encerrado'
   ORDER BY c.data_avaliacao DESC
");
$stmt->bind_param("s", $setor);
$stmt->execute();
$result = $stmt->get_result();
$encerrados = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Chamados Encerrados - <?= ucfirst($setor) ?></title>
  <link rel="stylesheet" href="../css/chamados_setores.css">
</head>
<body>

<h2>📁 Chamados Encerrados - Setor <?= ucfirst($setor) ?></h2>
<p>Visualize todos os chamados encerrados que foram tratados pelo setor de <?= ucfirst($setor) ?>.</p>

<table>
  <tr>
    <th>ID</th>
    <th>Loja</th>
    <th>Solicitante</th>
    <th>Descrição</th>
    <th>Solução</th>
    <th>Responsável</th>
    <th>Avaliação</th>
    <th>Justificativa</th>
    <th>Data Avaliação</th>
  </tr>
  <?php if (empty($encerrados)): ?>
    <tr><td colspan="9" style="text-align:center;">Nenhum chamado encerrado encontrado para o setor <?= ucfirst($setor) ?>.</td></tr>
  <?php else: ?>
    <?php foreach ($encerrados as $c): ?>
      <tr>
        <td><?= htmlspecialchars($c['id']) ?></td>
        <td><?= htmlspecialchars($c['loja_nome'] ?? '') ?></td>
        <td><?= htmlspecialchars($c['solicitante_nome'] ?? '') ?></td>
        <td><?= nl2br(htmlspecialchars($c['descricao'] ?? '')) ?></td>
        <td><?= nl2br(htmlspecialchars($c['solucao'] ?? '—')) ?></td>
        <td><?= htmlspecialchars($c['responsavel_nome'] ?? '—') ?></td>
        <td><?= htmlspecialchars($c['avaliacao'] ?? '—') ?></td>
        <td><?= !empty($c['justificativa']) ? nl2br(htmlspecialchars($c['justificativa'])) : '—' ?></td>
        <td><?= fmt_data_br($c['data_avaliacao'] ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
  <?php endif; ?>
</table>

<a class="btn" href="chamados_<?= $setor ?>.php" style="margin-top:20px;">🔙 Voltar ao painel <?= ucfirst($setor) ?></a>

</body>
</html>
