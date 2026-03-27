<?php
require_once '../includes/funcoes.php';
$conn = conectar();

header('Content-Type: application/json; charset=utf-8');

$q = $_GET['q'] ?? '';
if (strlen($q) < 2) {
  echo json_encode([]);
  exit;
}

$stmt = $conn->prepare("
  SELECT id, nome 
  FROM funcionarios 
  WHERE nome LIKE CONCAT('%', ?, '%') 
    AND desligamento IS NULL 
  ORDER BY nome ASC 
  LIMIT 10
");
$stmt->bind_param("s", $q);
$stmt->execute();
$res = $stmt->get_result();

$funcionarios = [];
while ($row = $res->fetch_assoc()) {
  $funcionarios[] = $row;
}
$stmt->close();

echo json_encode($funcionarios);
