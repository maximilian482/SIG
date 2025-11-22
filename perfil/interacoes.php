<?php
require_once __DIR__ . '/../dados/conexao.php';
session_start();

$id = $_SESSION['id_funcionario'] ?? null;

if (!$id) {
  header('Location: /login.php');
  exit;
}

$stmt = $conn->prepare("
  SELECT i.tipo, i.data, f.nome as remetente
  FROM interacoes i
  JOIN funcionarios f ON f.id = i.remetente_id
  WHERE i.funcionario_id = ?
  ORDER BY i.data DESC
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
?>

<h2>Interações recebidas</h2>
<ul>
<?php while ($row = $result->fetch_assoc()): ?>
  <li>
    <?= htmlspecialchars($row['remetente']) ?> 
    te parabenizou pelo <?= htmlspecialchars($row['tipo']) ?> 
    em <?= date('d/m/Y H:i', strtotime($row['data'])) ?>
  </li>
<?php endwhile; ?>
</ul>
