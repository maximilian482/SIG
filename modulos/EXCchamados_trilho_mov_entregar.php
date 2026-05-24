<?php
include_once("conexao.php");

$id = intval($_POST['id']);

$sql = "UPDATE trilho 
        SET status = 'entregue',
            data_entrega = NOW()
        WHERE id = $id";

if (mysqli_query($conn, $sql)) {
    echo "Entrega finalizada com sucesso!";
} else {
    echo "Erro ao finalizar: " . mysqli_error($conn);
}
?>
