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
  $ts = strtotime($value);
  return $ts ? date('d/m/Y H:i', $ts) : '—';
}

// --------- Filtros ---------
$where  = "WHERE LOWER(TRIM(c.status)) = 'encerrado'";
$params = [];
$types  = '';

// loja (geralmente inteiro)
if (!empty($_GET['loja'])) {
  $where   .= " AND c.loja_origem = ? ";
  $params[] = (int)$_GET['loja'];
  $types   .= 'i';
}

// setor destino (texto exato)
if (!empty($_GET['setor'])) {
  $where   .= " AND c.setor_destino = ? ";
  $params[] = $_GET['setor'];
  $types   .= 's';
}

// período de data de avaliação (strings de data)
if (!empty($_GET['data_ini'])) {
  $where   .= " AND c.data_avaliacao >= ? ";
  $params[] = $_GET['data_ini'] . " 00:00:00";
  $types   .= 's';
}
if (!empty($_GET['data_fim'])) {
  $where   .= " AND c.data_avaliacao <= ? ";
  $params[] = $_GET['data_fim'] . " 23:59:59";
  $types   .= 's';
}

// --------- Consulta única com filtros ---------
$sql = "
  SELECT c.id,
         c.codigo_chamado,
         l.nome AS loja_nome,
         f.nome AS solicitante_nome,
         c.setor_destino,
         c.descricao,
         c.solucao,
         c.data_solucao,
         r.nome AS responsavel_nome,
         c.avaliacao,
         c.justificativa,
         c.data_avaliacao
    FROM chamados c
    LEFT JOIN lojas l ON c.loja_origem = l.id
    LEFT JOIN funcionarios f ON c.solicitante_id = f.id
    LEFT JOIN funcionarios r ON c.responsavel_id = r.id
  $where
  ORDER BY c.data_avaliacao DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$encerrados = $result->fetch_all(MYSQLI_ASSOC);

// A partir daqui, renderize o HTML (tabela) usando $encerrados.
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Chamados Encerrados - Administração</title>
  <link rel="stylesheet" href="../css/chamados_setores.css">
</head>
<body>

<h2>📁 Chamados Encerrados - Administração</h2>
<p>Visualize todos os chamados encerrados em qualquer setor.</p>

<form method="GET" style="margin-bottom:20px">

  <!-- Filtro por Loja -->
  <label for="filtroLoja">Loja:</label>
  <select name="loja" id="filtroLoja">
    <option value="">Todas</option>
    <?php
    $resLojas = $conn->query("SELECT id, nome FROM lojas ORDER BY nome ASC");
    while ($loja = $resLojas->fetch_assoc()):
    ?>
      <option value="<?= $loja['id'] ?>"
        <?= (($_GET['loja'] ?? '') == $loja['id']) ? 'selected' : '' ?>>
        <?= htmlspecialchars($loja['nome']) ?>
      </option>
    <?php endwhile; ?>
  </select>

  <!-- Filtro por Setor -->
  <label for="filtroSetor">Setor destino:</label>
  <select name="setor" id="filtroSetor">
    <option value="">Todos</option>
    <?php
    $resSetores = $conn->query("SELECT DISTINCT setor_destino FROM chamados ORDER BY setor_destino ASC");
    while ($setor = $resSetores->fetch_assoc()):
      $nomeSetor = $setor['setor_destino'];
    ?>
      <option value="<?= htmlspecialchars($nomeSetor) ?>"
        <?= (($_GET['setor'] ?? '') == $nomeSetor) ? 'selected' : '' ?>>
        <?= htmlspecialchars($nomeSetor) ?>
      </option>
    <?php endwhile; ?>
  </select>

  <!-- Filtro por período -->
  <label for="filtroData">Data avaliação:</label>
  <input type="date" name="data_ini" value="<?= htmlspecialchars($_GET['data_ini'] ?? '') ?>">
  até
  <input type="date" name="data_fim" value="<?= htmlspecialchars($_GET['data_fim'] ?? '') ?>">

  <button type="submit">Filtrar</button>
  <button type="button" onclick="window.location.href='<?= basename($_SERVER['PHP_SELF']) ?>'">
    Limpar filtros
  </button>
</form>

<table>
  <tr>
    <th>Código</th>
    <th>Loja</th>
    <th>Solicitante</th>
    <th>Setor destino</th>
    <th>Descrição</th>
    <th>Solução</th>
    <th>Data Solução</th>
    <th>Responsável</th>
    <th>Avaliação</th>
    <th>Justificativa</th>
    <th>Data Avaliação</th>
  </tr>
  <?php if (empty($encerrados)): ?>
    <tr><td colspan="11" style="text-align:center;">Nenhum chamado encerrado encontrado.</td></tr>
  <?php else: ?>
    <?php foreach ($encerrados as $c): ?>
      <tr>
        <td><?= htmlspecialchars($c['codigo_chamado'] ?? $c['id']) ?></td>
        <td><?= htmlspecialchars($c['loja_nome'] ?? '') ?></td>
        <td><?= htmlspecialchars($c['solicitante_nome'] ?? '') ?></td>
        <td><?= htmlspecialchars($c['setor_destino'] ?? '') ?></td>
        <td><?= nl2br(htmlspecialchars($c['descricao'] ?? '')) ?></td>
        <td><?= nl2br(htmlspecialchars($c['solucao'] ?? '—')) ?></td>
        <td><?= fmt_data_br($c['data_solucao'] ?? '') ?></td>
        <td><?= htmlspecialchars($c['responsavel_nome'] ?? '—') ?></td>
        <td><?= htmlspecialchars($c['avaliacao'] ?? '—') ?></td>
        <td><?= !empty($c['justificativa']) ? nl2br(htmlspecialchars($c['justificativa'])) : '—' ?></td>
        <td><?= fmt_data_br($c['data_avaliacao'] ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
  <?php endif; ?>
</table>

<a class="btn" href="/modulos/chamados_admin.php" style="margin-top:20px;">🔙 Voltar</a>

</body>
</html>
