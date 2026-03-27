<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

$id          = intval($_POST['id'] ?? 0);
$loja_id     = intval($_POST['loja_id'] ?? 0);
$nome        = trim($_POST['nome'] ?? '');
$localizacao = trim($_POST['localizacao'] ?? '');
$descricao   = trim($_POST['descricao'] ?? '');


// ===============================
// VALIDAÇÃO
// ===============================
if ($loja_id <= 0 || empty($nome) || empty($localizacao)) {

    $_SESSION['flash'] = [
        'mensagem' => 'Preencha todos os campos obrigatórios.',
        'tipo' => 'error'
    ];

    header("Location: loja.php?id=" . $loja_id . "&aba=dispositivos");
    exit;
}


// ===============================
// EDITAR DISPOSITIVO
// ===============================
if ($id > 0) {

    $stmt = $conn->prepare("
        UPDATE lojas_dispositivos
        SET nome = ?, localizacao = ?, descricao = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sssi", $nome, $localizacao, $descricao, $id);
    $stmt->execute();

    $_SESSION['flash'] = [
        'mensagem' => 'Dispositivo atualizado com sucesso!',
        'tipo' => 'success'
    ];

} else {

    // ===============================
    // ADICIONAR DISPOSITIVO
    // ===============================
    $stmt = $conn->prepare("
        INSERT INTO lojas_dispositivos (loja_id, nome, localizacao, descricao)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $loja_id, $nome, $localizacao, $descricao);
    $stmt->execute();

    $_SESSION['flash'] = [
        'mensagem' => 'Dispositivo adicionado com sucesso!',
        'tipo' => 'success'
    ];
}


// ===============================
// REDIRECIONAR PARA A ABA CORRETA
// ===============================
header("Location: loja.php?id=" . $loja_id . "&aba=dispositivos");
exit;
