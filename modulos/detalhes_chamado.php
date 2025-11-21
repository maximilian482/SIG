<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
  echo "<p style='color:red;'>ID inválido.</p>";
  exit;
}

$stmt = $conn->prepare("
  SELECT c.id, c.codigo_chamado, c.titulo, c.descricao, c.setor_destino,
         l.nome AS nome_loja, c.data_abertura, c.status, f.nome AS solicitante
  FROM chamados c
  JOIN funcionarios f ON f.id = c.solicitante_id
  JOIN lojas l ON l.id = c.loja_origem
  WHERE c.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$chamado = $stmt->get_result()->fetch_assoc();

if (!$chamado) {
  echo "<p style='color:red;'>Chamado não encontrado.</p>";
  exit;
}
?>

<div>
  <p><strong>ID:</strong> <?= htmlspecialchars($chamado['id']) ?></p>
  <p><strong>Código:</strong> <?= htmlspecialchars($chamado['codigo_chamado']) ?></p>
  <p><strong>Título:</strong> <?= htmlspecialchars($chamado['titulo']) ?></p>
  <p><strong>Descrição:</strong> <?= nl2br(htmlspecialchars($chamado['descricao'])) ?></p>
  <p><strong>Setor:</strong> <?= htmlspecialchars($chamado['setor_destino']) ?></p>
  <p><strong>Loja:</strong> <?= htmlspecialchars($chamado['nome_loja']) ?></p>
  <p><strong>Data de abertura:</strong> <?= htmlspecialchars($chamado['data_abertura']) ?></p>
  <p><strong>Status:</strong> <?= htmlspecialchars($chamado['status']) ?></p>
  <p><strong>Solicitante:</strong> <?= htmlspecialchars($chamado['solicitante']) ?></p>
</div>
