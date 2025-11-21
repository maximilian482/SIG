<?php
session_start();
require_once '../dados/conexao.php';
date_default_timezone_set('America/Sao_Paulo');

$conn = conectar(); // garante que $conn existe

if (!isset($_SESSION['usuario'])) {
  header('Location: ../login.php');
  exit;
}

$lojaId = $_SESSION['loja'] ?? 0;
$paginaAtual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$porPagina = 10;
$inicio = ($paginaAtual - 1) * $porPagina;

// Buscar inconformidades encerradas da loja logada
$stmt = $conn->prepare("
  SELECT i.*, f.nome AS solicitante
  FROM inconformidades i
  JOIN funcionarios f ON f.id = i.solicitante_id
  WHERE i.status = 'Encerrado' AND i.loja_id = ?
  ORDER BY i.encerramento_data DESC
  LIMIT ?, ?
");
$stmt->bind_param("iii", $lojaId, $inicio, $porPagina);
$stmt->execute();
$inconformidades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


// Total para paginação
$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM inconformidades WHERE status = 'Encerrado' AND loja_id = ?");
$countStmt->bind_param("i", $lojaId);
$countStmt->execute();
$totalRegistros = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;
$totalPaginas = ceil($totalRegistros / $porPagina);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Inconformidades Encerradas - Minha Loja</title>
  <link rel="stylesheet" href="../css/chamados.css">
</head>
<body>

<h2>📁 Inconformidades Encerradas - Minha Loja</h2>

<table>
  <thead>
    <tr>
      <th>Título</th>
      <th>Descrição</th>
      <th>Abertura</th>
      <th>Encerramento</th>
      <th>Resposta do gerente</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($inconformidades)): ?>
      <tr><td colspan="5" style="text-align:center;">Nenhuma inconformidade encerrada encontrada.</td></tr>
    <?php else: ?>
      <?php foreach ($inconformidades as $i): ?>
        <tr>
          <td><?= htmlspecialchars($i['titulo']) ?></td>
          <td><?= nl2br(htmlspecialchars($i['descricao'])) ?></td>
          <td><?= date('d/m/Y H:i', strtotime($i['abertura'])) ?></td>
          <td><?= !empty($i['encerramento_data']) ? date('d/m/Y H:i', strtotime($i['encerramento_data'])) : '—' ?></td>
          <td><?= nl2br(htmlspecialchars($i['solucao'] ?? '—')) ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<?php if ($totalPaginas > 1): ?>
  <div style="margin-top:20px; text-align:center;">
    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
      <a href="?pagina=<?= $i ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<div style="margin: 50px;">
<a class="btn" href="painel_tratamento_inconformidades.php">🔙 Voltar</a>
</div>

</body>
</html>
