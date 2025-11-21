<?php
session_start();
require_once '../dados/conexao.php';
date_default_timezone_set('America/Sao_Paulo');

// cria a conexão
$conn = conectar(); // se conexao.php já define $conn, essa linha pode não ser necessária

function normalizar($texto) {
  return strtolower(trim(str_replace(["\n", "\r"], '', $texto)));
}


if (!isset($_SESSION['usuario'])) {
  header('Location: ../login.php');
  exit;
}

$usuario = $_SESSION['usuario'];
$cargo   = normalizar($_SESSION['cargo'] ?? '');
$loja    = $_SESSION['loja'] ?? '';
$perfil  = $_SESSION['perfil'] ?? '';

$filtroLoja   = $_GET['loja'] ?? '';
$paginaAtual  = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$porPagina    = 10;
$inicio       = ($paginaAtual - 1) * $porPagina;

// Buscar lojas
$lojas = [];
$resLoja = $conn->query("SELECT id, nome FROM lojas ORDER BY nome");
while ($l = $resLoja->fetch_assoc()) {
  $lojas[$l['id']] = $l['nome'];
}

// Buscar inconformidades encerradas
$query = "SELECT i.*, f.nome AS solicitante 
          FROM inconformidades i 
          JOIN funcionarios f ON f.id = i.solicitante_id 
          WHERE i.status = 'Encerrado'";

$params = [];
$types  = '';

if ($filtroLoja) {
  $query .= " AND i.loja_id = ?";
  $params[] = $filtroLoja;
  $types   .= 'i';
}

$query .= " ORDER BY i.abertura DESC LIMIT ?, ?";
$params[] = $inicio;
$params[] = $porPagina;
$types   .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$inconformidades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Total para paginação
$countQuery = "SELECT COUNT(*) AS total FROM inconformidades WHERE status = 'Encerrado'";
if ($filtroLoja) {
  $countQuery .= " AND loja_id = " . intval($filtroLoja);
}
$totalRegistros = $conn->query($countQuery)->fetch_assoc()['total'] ?? 0;
$totalPaginas   = ceil($totalRegistros / $porPagina);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Inconformidades Encerradas</title>
  <link rel="stylesheet" href="../css/chamados.css">
 
</head>
<body>

<h2>📁 Inconformidades Encerradas</h2>
<p>Histórico de inconformidades resolvidas pelas lojas.</p>

<form method="GET" style="margin:20px 0;">
  <label>Loja:</label>
  <select name="loja">
    <option value="">Todas</option>
    <?php foreach ($lojas as $id => $nome): ?>
      <option value="<?= $id ?>" <?= $filtroLoja == $id ? 'selected' : '' ?>><?= htmlspecialchars($nome) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit">🔍 Filtrar</button>
</form>

<table>
  <thead>
    <tr>
      <th>Loja</th>
      <th>Título</th>
      <th>Descrição</th>
      <th>Abertura</th>
      <th>Tempo aberto</th>
      <th>Tratamento</th>
      <th>Avaliação</th>
      <th>Reabertura</th>
      <th>Data de reabertura</th>
      <th>Data de encerramento</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($inconformidades)): ?>
      <tr><td colspan="11" style="text-align:center;">Nenhuma inconformidade encerrada encontrada.</td></tr>
    <?php else: ?>
      <?php foreach ($inconformidades as $i): ?>
        <?php
          $aberturaTs     = strtotime($i['abertura']);
          $encerramentoTs = strtotime($i['encerramento_data'] ?? '');
          $tempoAberto    = '—';
          if ($aberturaTs && $encerramentoTs) {
            $diff  = $encerramentoTs - $aberturaTs;
            $dias  = floor($diff / 86400);
            $horas = floor(($diff % 86400) / 3600);
            $min   = floor(($diff % 3600) / 60);
            $tempoAberto = $dias > 0 ? "{$dias}d {$horas}h" : ($horas > 0 ? "{$horas}h {$min}m" : "{$min}m");
          }
        ?>
        <tr>
          <td><?= htmlspecialchars($lojas[$i['loja_id']] ?? $i['loja_id']) ?></td>
          <td><?= htmlspecialchars($i['titulo']) ?></td>
          <td><?= nl2br(htmlspecialchars($i['descricao'])) ?></td>
          <td><?= date('d/m/Y H:i', $aberturaTs) ?></td>
          <td><?= $tempoAberto ?></td>
          <td><?= nl2br(htmlspecialchars($i['solucao'] ?? '—')) ?></td>
          <td><?= htmlspecialchars($i['avaliacao'] ?? '—') ?></td>
          <td><?= nl2br(htmlspecialchars($i['avaliacao_justificativa'] ?? '—')) ?></td>
          <td>
            <?php if (!empty($i['reabertura_data'])): ?>
              <?= date('d/m/Y H:i', strtotime($i['reabertura_data'])) ?>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($i['encerramento_data'])): ?>
              <?= date('d/m/Y H:i', strtotime($i['encerramento_data'])) ?>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td style="background:#d4edda; color:#155724; padding:4px; border-radius:4px;">Encerrado</td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<?php if ($totalPaginas > 1): ?>
  <div style="margin-top:20px; text-align:center;">
    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
      <?php
        $link = "?pagina=$i";
        if ($filtroLoja) $link .= "&loja=" . urlencode($filtroLoja);
      ?>
      <a href="<?= $link ?>" style="margin:0 6px;">[<?= $i ?>]</a>
    <?php endfor; ?>
  </div>
<?php endif; ?>


    <div style="margin: 50px;">
      <a class="btn" href="inconformidade_lojas.php">🔙 Voltar</a>
    </div>
</body>
</html>
