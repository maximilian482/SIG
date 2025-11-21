<?php
session_start();
require_once '../includes/funcoes.php'; // ajuste se sua função conectar() estiver em outro caminho
date_default_timezone_set('America/Sao_Paulo');

$conn = conectar();

if (!isset($_SESSION['usuario'])) {
  header('Location: ../login.php');
  exit;
}

// Dados da sessão
$usuario      = $_SESSION['usuario'] ?? '';
$nomeUsuario  = $_SESSION['nome'] ?? $usuario;
$lojaUsuario  = intval($_SESSION['loja'] ?? 0);
$cargo        = strtolower(trim($_SESSION['cargo'] ?? ''));
$usuarioId    = intval($_SESSION['funcionario_id'] ?? 0);

// Filtro e paginação
$filtroSetor = $_GET['setor'] ?? '';
$paginaAtual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$porPagina   = 10;
$inicio      = ($paginaAtual - 1) * $porPagina;

// Montar cláusula WHERE
$where  = "c.loja_origem = ? AND LOWER(c.status) = 'encerrado'";
$params = [$lojaUsuario];
$types  = "i";

if (!empty($filtroSetor)) {
  $where     .= " AND c.setor_destino = ?";
  $params[]   = $filtroSetor;
  $types     .= "s";
}

// Consulta chamados encerrados
$query = "
  SELECT
    c.id,
    c.titulo,
    c.setor_destino,
    c.descricao,
    c.status,
    c.solucao,
    c.avaliacao,            -- 'Sim' ou 'Não' pelo solicitante
    c.justificativa,        -- justificativa do solicitante (NULL quando 'Sim')
    c.data_abertura,
    f.nome AS responsavel
  FROM chamados c
  LEFT JOIN funcionarios f ON f.id = c.responsavel_id
  WHERE $where
  ORDER BY c.data_abertura DESC
  LIMIT ?, ?
";

$params[] = $inicio;
$params[] = $porPagina;
$types   .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$resultado = $stmt->get_result();
$chamados  = $resultado->fetch_all(MYSQLI_ASSOC);

// Contar total para paginação
$whereTotal   = "loja_origem = ? AND LOWER(status) = 'encerrado'";
$paramsTotal  = [$lojaUsuario];
$typesTotal   = "i";

if (!empty($filtroSetor)) {
  $whereTotal   .= " AND setor_destino = ?";
  $paramsTotal[] = $filtroSetor;
  $typesTotal   .= "s";
}

$stmtTotal = $conn->prepare("SELECT COUNT(*) AS total FROM chamados WHERE $whereTotal");
$stmtTotal->bind_param($typesTotal, ...$paramsTotal);
$stmtTotal->execute();
$totalChamados = intval($stmtTotal->get_result()->fetch_assoc()['total'] ?? 0);

// Utilitário de tempo aberto (em d/h/m)
function tempoAbertoStr(?string $dataAbertura): string {
  if (!$dataAbertura) return '—';
  $aberturaTs = strtotime($dataAbertura);
  if (!$aberturaTs) return '—';
  $diff  = time() - $aberturaTs;
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
  <title>📁 Chamados Encerrados</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/chamados.css">
  <style>
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 8px; vertical-align: top; }
    th { background: #f5f5f5; }
    .badge { display:inline-block; padding:2px 6px; border-radius:4px; font-size:12px; }
    .badge-ok { background:#d4edda; color:#155724; }
    .badge-no { background:#f8d7da; color:#721c24; }
    .descricao, .solucao, .justificativa { white-space: pre-wrap; }
  </style>
</head>
<body>

<h2>📁 Chamados Encerrados</h2>

<form method="GET" style="margin-bottom:20px;">
  <label for="setor">🔍 Filtrar por setor:</label>
  <select name="setor" id="setor" onchange="this.form.submit()">
    <option value="">— Todos os setores —</option>
    <option value="TI" <?= (isset($_GET['setor']) && $_GET['setor'] === 'TI') ? 'selected' : '' ?>>TI</option>
    <option value="Manutenção" <?= (isset($_GET['setor']) && $_GET['setor'] === 'Manutencao') ? 'selected' : '' ?>>Manutenção</option>
    <option value="Supervisão" <?= (isset($_GET['setor']) && $_GET['setor'] === 'Supervisao') ? 'selected' : '' ?>>Supervisão</option>
    <option value="Financeiro" <?= (isset($_GET['setor']) && $_GET['setor'] === 'Financeiro') ? 'selected' : '' ?>>Financeiro</option>
    <option value="RH" <?= (isset($_GET['setor']) && $_GET['setor'] === 'RH') ? 'selected' : '' ?>>RH</option>
    <option value="Compras" <?= (isset($_GET['setor']) && $_GET['setor'] === 'Compras') ? 'selected' : '' ?>>Compras</option>
  </select>
</form>

<table>
<tr>
  <th>ID</th>
  <th>Título</th>
  <th>Setor</th>
  <th>Descrição</th>
  <th>Status</th>
  <th>Solução</th>
  <th>Avaliação do solicitante</th>
  <th>Justificativa do solicitante</th>
  <th>Responsável</th>
  <th>Tempo aberto</th>
</tr>

<?php if (empty($chamados)): ?>
  <tr><td colspan="10" style="text-align:center;">Nenhum chamado encerrado encontrado.</td></tr>
<?php else: ?>
  <?php foreach ($chamados as $c): ?>
    <?php
      $tempoAberto = tempoAbertoStr($c['data_abertura'] ?? null);
      $statusLabel = ucfirst(strtolower($c['status'] ?? ''));
      $avaliacao   = $c['avaliacao'] ?? null;        // 'Sim' | 'Não' | null
      $justif      = $c['justificativa'] ?? null;    // texto | null
      $badgeHtml   = '—';
      if ($avaliacao === 'Sim') {
        $badgeHtml = '<span class="badge badge-ok">Atendido (Sim)</span>';
      } elseif ($avaliacao === 'Não') {
        $badgeHtml = '<span class="badge badge-no">Não atendido (Não)</span>';
      }
    ?>
    <tr>
      <td><?= htmlspecialchars($c['id']) ?></td>
      <td><?= htmlspecialchars($c['titulo'] ?? '—') ?></td>
      <td><?= htmlspecialchars($c['setor_destino'] ?? '—') ?></td>
      <td class="descricao"><?= nl2br(htmlspecialchars($c['descricao'] ?? '—')) ?></td>
      <td><?= htmlspecialchars($statusLabel) ?></td>
      <td class="solucao"><?= nl2br(htmlspecialchars($c['solucao'] ?? '—')) ?></td>
      <td><?= $badgeHtml ?></td>
      <td class="justificativa"><?= !empty($justif) ? nl2br(htmlspecialchars($justif)) : '—' ?></td>
      <td><?= htmlspecialchars($c['responsavel'] ?? '—') ?></td>
      <td><?= htmlspecialchars($tempoAberto) ?></td>
    </tr>
  <?php endforeach; ?>
<?php endif; ?>
</table>

<?php
$totalPaginas = max(1, ceil($totalChamados / $porPagina));
if ($totalPaginas > 1): ?>
  <div style="margin-top:20px; text-align:center;">
    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
      <a href="?pagina=<?= $i ?>&setor=<?= urlencode($filtroSetor) ?>" style="margin:0 6px; <?= $i == $paginaAtual ? 'font-weight:bold;' : '' ?>">[<?= $i ?>]</a>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<div style="margin-top:20px;">
  <a class="btn" href="acompanhar_chamados_publico.php">🔙 Voltar para chamados</a>
</div>

</body>
</html>
