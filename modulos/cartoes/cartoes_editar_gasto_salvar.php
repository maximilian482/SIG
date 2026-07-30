<?php
session_start();

require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../dados/conexao.php';

$conn = conectar();

// Dados enviados
$id_compra      = $_POST['id_compra'];
$id_ciclo       = $_POST['id_ciclo'];
$cpf            = $_POST['cpf_funcionario'];
$cartao         = $_POST['codigo_cartao'];
$id_atribuicao  = $_POST['id_atribuicao'];

$data_compra    = $_POST['data_compra'];
$descricao      = $_POST['descricao'];
$valor_total    = floatval($_POST['valor_total']);
$parcelas       = intval($_POST['parcelas']);

$valor_parcela = $valor_total / $parcelas;

// Buscar nome e setor do funcionário
$stmtFunc = $conn->prepare("
    SELECT nome, id_setor
    FROM funcionarios
    WHERE cpf = ?
    LIMIT 1
");
$stmtFunc->bind_param("s", $cpf);
$stmtFunc->execute();
$func = $stmtFunc->get_result()->fetch_assoc();

$nome_funcionario = $func['nome'];
$id_setor         = $func['id_setor'];

// Buscar vencimento do cartão
$stmtV = $conn->prepare("SELECT vencimento_dia FROM cartoes WHERE codigo_cartao = ?");
$stmtV->bind_param("s", $cartao);
$stmtV->execute();
$vencimento = intval($stmtV->get_result()->fetch_assoc()['vencimento_dia'] ?? 1);

// Calcular competência inicial
$diaCompra = intval(date('d', strtotime($data_compra)));
$mesCompra = intval(date('m', strtotime($data_compra)));
$anoCompra = intval(date('Y', strtotime($data_compra)));

if ($diaCompra <= $vencimento) {
    $compMes = $mesCompra;
    $compAno = $anoCompra;
} else {
    $compMes = $mesCompra + 1;
    $compAno = $anoCompra;

    if ($compMes > 12) {
        $compMes = 1;
        $compAno++;
    }
}

// Buscar comprovante antigo
$stmtComp = $conn->prepare("
    SELECT comprovante
    FROM cartoes_gastos
    WHERE id_compra = ?
    LIMIT 1
");
$stmtComp->bind_param("i", $id_compra);
$stmtComp->execute();
$comprovante = $stmtComp->get_result()->fetch_assoc()['comprovante'] ?? null;

// EXCLUI TODAS AS PARCELAS ANTIGAS
$stmtDelete = $conn->prepare("
    DELETE FROM cartoes_gastos
    WHERE id_compra = ?
");
$stmtDelete->bind_param("i", $id_compra);
$stmtDelete->execute();

// RECRIAR TODAS AS PARCELAS DO ZERO
$stmtInsert = $conn->prepare("
    INSERT INTO cartoes_gastos
    (id_compra, id_atribuicao, codigo_cartao, cpf_funcionario, nome_funcionario, id_setor,
     data_compra, descricao, valor_parcela, comprovante, criado_em,
     parcelas, total_parcelas,
     competencia_mes, competencia_ano,
     id_ciclo)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)
");

for ($i = 1; $i <= $parcelas; $i++) {

    // Competência da parcela i
    $compMesParcela = $compMes + ($i - 1);
    $compAnoParcela = $compAno;

    while ($compMesParcela > 12) {
        $compMesParcela -= 12;
        $compAnoParcela++;
    }

  $stmtInsert->bind_param(
    "iisssissdsiiiii",
    $id_compra,
    $id_atribuicao,
    $cartao,
    $cpf,
    $nome_funcionario,
    $id_setor,
    $data_compra,
    $descricao,
    $valor_parcela,
    $comprovante,
    $i,
    $parcelas,
    $compMesParcela,
    $compAnoParcela,
    $id_ciclo
);


    $stmtInsert->execute();
}

// Auditoria
$stmtHist = $conn->prepare("
    INSERT INTO cartoes_historico
    (codigo_cartao, acao, descricao, id_atribuicao, data_hora, usuario)
    VALUES (?, 'GASTO EDITADO', ?, ?, NOW(), ?)
");

$descHist = "Gasto editado pelo funcionário {$cpf}";

$stmtHist->bind_param(
    "ssis",
    $cartao,
    $descHist,
    $id_atribuicao,
    $cpf
);

$stmtHist->execute();

// Sucesso
$_SESSION['flash'] = [
    'mensagem' => 'Gasto atualizado com sucesso!',
    'tipo' => 'sucesso'
];

header("Location: cartoes_funcionario.php");
exit;
?>
