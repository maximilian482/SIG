<?php
session_start();

require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

header('Content-Type: application/json; charset=utf-8');

// Verifica login
if (!isset($_SESSION['cpf'])) {
    echo json_encode(["erro" => "Acesso negado"]);
    exit;
}

$cpf = $_SESSION['cpf'];

if (!temAcesso($conn, $cpf, 'ferramentas_auditoria_pp')) {
    echo json_encode(["erro" => "Sem permissão"]);
    exit;
}

$lojaId = intval($_GET['loja_id'] ?? 0);

if ($lojaId <= 0) {
    echo json_encode([]);
    exit;
}

/*
---------------------------------------------------------
1) BUSCAR ITENS ATIVOS PARA ESSA LOJA
---------------------------------------------------------
*/
$sql = "
    SELECT c.id, c.pergunta
    FROM auditoria_pp_config c
    JOIN auditoria_pp_config_ativos a ON a.item_id = c.id
    WHERE a.loja_id = ?
    ORDER BY c.id
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$res = $stmt->get_result();

$itens = [];

while ($row = $res->fetch_assoc()) {
    $itens[] = [
        'id'       => (int)$row['id'],
        'pergunta' => $row['pergunta'],
    ];
}

/*
---------------------------------------------------------
2) RETORNO FINAL
---------------------------------------------------------
*/
echo json_encode($itens);
