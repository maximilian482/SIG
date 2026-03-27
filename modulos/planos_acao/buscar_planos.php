<?php
require_once __DIR__ . '/../../includes/funcoes.php';
$conn = conectar();

$q = $_GET['q'] ?? '';
$q = trim($q);

$sql = "
    SELECT id, titulo
    FROM planos_acao
    WHERE titulo LIKE ?
    ORDER BY id DESC
    LIMIT 20
";

$stmt = $conn->prepare($sql);
$like = "%$q%";
$stmt->bind_param("s", $like);
$stmt->execute();

$result = $stmt->get_result();
$planos = [];

while ($row = $result->fetch_assoc()) {
    $planos[] = [
        'id' => $row['id'],
        'titulo' => $row['titulo']
    ];
}

header('Content-Type: application/json');
echo json_encode($planos);
