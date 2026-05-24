<?php
session_start();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php'; // ← AQUI entra o timezone
require_once ROOT_PATH . '/dados/conexao.php';

$conn = conectar();

$id = intval($_GET['id'] ?? 0);
$filial = intval($_GET['filial'] ?? 0);

$nomeFarmaceutico = $_SESSION['usuario'] ?? 'Farmacêutico';
$dataAgora = date('Y-m-d H:i:s'); // agora com America/Sao_Paulo

if ($id > 0) {
    $stmt = $conn->prepare("
        UPDATE controlados 
        SET conferido = 1,
            conferido_por = ?,
            conferido_em = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", $nomeFarmaceutico, $dataAgora, $id);
    $stmt->execute();

    $_SESSION['flash'] = [
        'mensagem' => 'Registro conferido com sucesso!',
        'tipo' => 'success'
    ];
}

header("Location: controlados_registros_farmaceutico.php");
exit;
