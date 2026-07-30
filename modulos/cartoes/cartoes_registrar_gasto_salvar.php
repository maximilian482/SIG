<?php
session_start();

require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../dados/conexao.php';

$conn = conectar();

// Verifica login
$cpf = $_SESSION['cpf'] ?? '';
if (!$cpf) {
    header("Location: ../../login.php");
    exit;
}

// Dados enviados
$cartao         = $_POST['cartao'] ?? null;
$id_atribuicao  = $_POST['id_atribuicao'] ?? null;
$id_setor       = $_POST['id_setor'] ?? null;
$id_ciclo       = $_POST['id_ciclo'] ?? null;

$data           = $_POST['data_compra'] ?? null;
$descricao      = $_POST['descricao'] ?? null;
$valorTotal     = floatval($_POST['valor'] ?? 0);
$parcelas       = intval($_POST['parcelas'] ?? 1);

$arquivo        = $_FILES['comprovante'] ?? null;

// Validação básica
if (!$cartao || !$id_atribuicao || !$id_ciclo || !$data || !$descricao || !$valorTotal || !$arquivo) {
    $_SESSION['flash'] = [
        'mensagem' => 'Preencha todos os campos (incluindo ciclo).',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_registrar_gasto.php?cartao=$cartao");
    exit;
}

// Busca nome do funcionário
$stmt = $conn->prepare("
    SELECT nome
    FROM funcionarios
    WHERE cpf = ?
    LIMIT 1
");
$stmt->bind_param("s", $cpf);
$stmt->execute();
$nome_funcionario = $stmt->get_result()->fetch_assoc()['nome'] ?? '';

// Upload do comprovante
$ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
    $_SESSION['flash'] = [
        'mensagem' => 'Formato de comprovante inválido. Envie JPG, PNG ou PDF.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_registrar_gasto.php?cartao=$cartao");
    exit;
}

$nomeArquivo = time() . "_" . rand(1000,9999) . "." . $ext;
$caminho = __DIR__ . "/../../uploads/comprovantes/" . $nomeArquivo;

move_uploaded_file($arquivo['tmp_name'], $caminho);

// Valor por parcela
$valorParcela = $valorTotal / $parcelas;

// Calcula competência (mês/ano)
$diaCompra = intval(date('d', strtotime($data)));
$mesCompra = intval(date('m', strtotime($data)));
$anoCompra = intval(date('Y', strtotime($data)));

$stmtV = $conn->prepare("SELECT vencimento_dia FROM cartoes WHERE codigo_cartao = ?");
$stmtV->bind_param("s", $cartao);
$stmtV->execute();
$vencimento = intval($stmtV->get_result()->fetch_assoc()['vencimento_dia'] ?? 1);

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

// ID único da compra
$id_compra = time();

// Preparar INSERT (SEM A COLUNA valor)
$stmt2 = $conn->prepare("
   INSERT INTO cartoes_gastos
        (id_compra, id_atribuicao, codigo_cartao, cpf_funcionario, nome_funcionario, id_setor,
        data_compra, descricao, valor_parcela, comprovante, criado_em,
        parcelas, total_parcelas,
        competencia_mes, competencia_ano,
        id_ciclo)

    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)
");

// Loop das parcelas
for ($i = 1; $i <= $parcelas; $i++) {

    $compMesParcela = $compMes + ($i - 1);
    $compAnoParcela = $compAno;

    while ($compMesParcela > 12) {
        $compMesParcela -= 12;
        $compAnoParcela++;
    }

    // 15 variáveis → 15 tipos
    $stmt2->bind_param(
    "iisssissdsiiiii",
        $id_compra,
        $id_atribuicao,
        $cartao,
        $cpf,
        $nome_funcionario,
        $id_setor,
        $data,
        $descricao,
        $valorParcela,
        $nomeArquivo,
        $i,
        $parcelas,
        $compMesParcela,
        $compAnoParcela,
        $id_ciclo
    );


    $stmt2->execute();
}

// Feedback
$_SESSION['flash'] = [
    'mensagem' => 'Gasto registrado com sucesso!',
    'tipo' => 'sucesso'
];

header("Location: cartoes_funcionario.php");
exit;
?>
