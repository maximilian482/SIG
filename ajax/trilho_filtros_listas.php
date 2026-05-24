<?php
require_once __DIR__ . '/../dados/conexao.php';
$conn = conectar();

// Sempre deixa claro que é JSON
header('Content-Type: application/json; charset=utf-8');

// Se der erro no banco, não manda HTML misturado
$conn->set_charset('utf8mb4');

// Lojas solicitantes (origem)
$origensRes = $conn->query("
    SELECT id, nome 
    FROM lojas 
    ORDER BY nome ASC
");

$destinosRes = $conn->query("
    SELECT id, nome 
    FROM lojas 
    ORDER BY nome ASC
");

$origens  = $origensRes  ? $origensRes->fetch_all(MYSQLI_ASSOC) : [];
$destinos = $destinosRes ? $destinosRes->fetch_all(MYSQLI_ASSOC) : [];

echo json_encode([
    'origens'  => $origens,
    'destinos' => $destinos,
], JSON_UNESCAPED_UNICODE);
