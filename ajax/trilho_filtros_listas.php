<?php
require_once __DIR__ . '/../dados/conexao.php';
$conn = conectar();

// Lojas solicitantes (origem)
$origens = $conn->query("
    SELECT id, nome 
    FROM lojas 
    ORDER BY nome ASC
")->fetch_all(MYSQLI_ASSOC);

// Lojas de liberação (destino)
$destinos = $conn->query("
    SELECT id, nome 
    FROM lojas 
    ORDER BY nome ASC
")->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    "origens" => $origens,
    "destinos" => $destinos
]);
