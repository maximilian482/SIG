<?php
require_once '../dados/conexao.php';
$conn = conectar();

if (!isset($_GET['id'])) {
    echo "ID não informado.";
    exit;
}

$id = intval($_GET['id']);

/*
---------------------------------------------------------
1) EXCLUIR ITENS DA AUDITORIA
---------------------------------------------------------
*/
$sqlItens = "DELETE FROM auditoria_pp_itens WHERE auditoria_id = $id";
$conn->query($sqlItens);

/*
---------------------------------------------------------
2) EXCLUIR CABEÇALHO DA AUDITORIA
---------------------------------------------------------
*/
$sqlAuditoria = "DELETE FROM auditoria_pp WHERE id = $id";
$conn->query($sqlAuditoria);

/*
---------------------------------------------------------
3) RETORNO
---------------------------------------------------------
*/
echo "OK";
exit;
