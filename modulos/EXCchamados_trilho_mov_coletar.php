<?php
include_once("conexao.php");
session_start();

$motoboy_id = $_SESSION['usuario_id'];
$id = intval($_POST['id']);

$sql = "UPDATE trilho 
        SET status = 'em_rota',
            motoboy_id = '$motoboy_id',
            data_coleta = NOW()
        WHERE id = $id";

if (mysqli_query($conn, $sql)) {
    echo "Coleta registrada com sucesso!";
} else {
    echo "Erro ao coletar: " . mysqli_error($conn);
}
?>
