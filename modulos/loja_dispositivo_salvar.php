<?php
require_once '../dados/conexao.php';
$conn = conectar();

$loja_id     = intval($_POST['loja_id']);
$id          = intval($_POST['id']);
$nome        = trim($_POST['nome']);
$localizacao = trim($_POST['localizacao']);
$descricao   = trim($_POST['descricao']);

if ($id > 0) {
    // EDITAR
    $stmt = $conn->prepare("
        UPDATE lojas_dispositivos
        SET nome = ?, localizacao = ?, descricao = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sssi", $nome, $localizacao, $descricao, $id);
} else {
    // ADICIONAR
    $stmt = $conn->prepare("
        INSERT INTO lojas_dispositivos (loja_id, nome, localizacao, descricao)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $loja_id, $nome, $localizacao, $descricao);
}

$stmt->execute();

header("Location: loja.php?id=$loja_id&aba=dispositivos");
exit;
