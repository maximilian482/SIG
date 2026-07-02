<?php
require_once '../dados/conexao.php';
$conn = conectar();

$sql = "SELECT id, nome FROM lojas ORDER BY nome ASC";
$res = $conn->query($sql);

$lojas = [];

while ($r = $res->fetch_assoc()) {
    $lojas[] = [
        "id" => $r["id"],
        "nome" => $r["nome"]
    ];
}

echo json_encode($lojas);
