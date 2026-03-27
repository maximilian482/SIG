<?php
require_once '../includes/funcoes.php';
$conn = conectar();

$id = intval($_GET['id'] ?? 0);

$sql = "
  SELECT i.*, 
         c.nome AS categoria_nome,
         c.sigla AS categoria_sigla,
         l.nome AS loja_nome,
         f.nome AS responsavel_nome
  FROM inventario i
  JOIN inventario_categorias c ON c.id = i.categoria_id
  JOIN lojas l ON l.id = i.loja_id
  JOIN funcionarios f ON f.id = i.responsavel_id
  WHERE i.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

echo json_encode($item);
