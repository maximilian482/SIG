<?php
require_once '../dados/conexao.php';
$conn = conectar();

header("Content-Type: text/plain; charset=utf-8");

// Verifica se recebeu o ID
$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    echo "ID inválido";
    exit;
}

/*
-----------------------------------------
1) EXCLUIR DA TABELA BASE
-----------------------------------------
*/
$sqlDelItem = "DELETE FROM auditoria_pp_config WHERE id = $id";
$conn->query($sqlDelItem);

/*
-----------------------------------------
2) EXCLUIR DOS ATIVOS (todas as lojas)
-----------------------------------------
*/
$sqlDelAtivos = "DELETE FROM auditoria_pp_config_ativos WHERE item_id = $id";
$conn->query($sqlDelAtivos);

/*
-----------------------------------------
3) RETORNO
-----------------------------------------
*/
echo "OK";
exit;
