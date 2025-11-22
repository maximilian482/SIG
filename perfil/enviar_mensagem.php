<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destinatarioId = (int)$_POST['funcionario_id'];   // colaborador
    $remetenteId    = $_SESSION['usuario_id'] ?? 0;    // usuário logado
    $conteudo       = trim($_POST['mensagem'] ?? '');

    if ($remetenteId > 0 && $destinatarioId > 0 && $conteudo !== '') {
        $sql = "INSERT INTO mensagens (remetente_id, destinatario_id, conteudo) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $remetenteId, $destinatarioId, $conteudo);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: ../modulos/comunidade.php?aba=colaboradores");
    exit;
}
?>
