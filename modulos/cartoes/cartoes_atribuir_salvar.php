<?php
session_start();

require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../dados/conexao.php';

$conn = conectar();

// CPF sempre limpo e padronizado
$cpfLogado = trim(preg_replace('/\D/', '', $_SESSION['cpf'] ?? ''));

// Verifica acesso pelo EDITAR ACESSOS
if (!temAcesso($conn, $cpfLogado, 'cartoes')) {
    $_SESSION['flash'] = [
        'mensagem' => 'Você não possui acesso ao módulo de Cartões Corporativos.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_mestre.php");
    exit;
}


// Dados recebidos
$editar_id       = $_POST['editar_id'] ?? null;
$id_ciclo        = $_POST['id_ciclo'] ?? null; // ciclo existente (edição)
$codigo_cartao   = $_POST['codigo_cartao'] ?? null;
$cpf_funcionario = $_POST['cpf_funcionario'] ?? null;
$saldo_atual     = floatval($_POST['saldo_atual'] ?? 0);

// Verifica funcionário
$stmt = $conn->prepare("SELECT nome, id_setor FROM funcionarios WHERE cpf = ?");
$stmt->bind_param("s", $cpf_funcionario);
$stmt->execute();
$func = $stmt->get_result()->fetch_assoc();

if (!$func) {
    $_SESSION['flash'] = [
        'mensagem' => 'Funcionário inválido.',
        'tipo' => 'erro'
    ];
    header("Location: cartoes_atribuir.php");
    exit;
}

// Nome reduzido
$nomeCompleto = trim($func['nome']);
$partes = explode(" ", $nomeCompleto);
$nomeReduzido = $partes[0] . " " . end($partes);

// ===============================
//   EDIÇÃO DE ATRIBUIÇÃO
// ===============================
if ($editar_id) {

    // Busca cartão antigo antes da edição
    $stmtOld = $conn->prepare("SELECT codigo_cartao FROM cartoes_atribuicoes WHERE id = ?");
    $stmtOld->bind_param("i", $editar_id);
    $stmtOld->execute();
    $oldData = $stmtOld->get_result()->fetch_assoc();
    $oldCartao = $oldData['codigo_cartao'];

    // Atualiza atribuição existente (mantém id_ciclo)
    $stmt2 = $conn->prepare("
        UPDATE cartoes_atribuicoes
        SET codigo_cartao = ?, 
            cpf_funcionario = ?, 
            saldo_entregue = ?,
            id_ciclo = ?
        WHERE id = ?
    ");
    $stmt2->bind_param("ssdii", 
        $codigo_cartao, 
        $cpf_funcionario, 
        $saldo_atual,
        $id_ciclo,
        $editar_id
    );
    $stmt2->execute();

    // Se o cartão foi trocado → liberar o antigo
    if ($oldCartao !== $codigo_cartao) {
        $conn->query("
            UPDATE cartoes
            SET status = 'DISPONÍVEL',
                ultima_movimentacao = NOW()
            WHERE codigo_cartao = '$oldCartao'
        ");
    }

    // Atualiza cartão novo → aguardando assinatura
    $stmt3 = $conn->prepare("
        UPDATE cartoes
        SET saldo_atual = ?, 
            status = 'AGUARDANDO ASSINATURA',
            ultima_movimentacao = NOW()
        WHERE codigo_cartao = ?
    ");
    $stmt3->bind_param("ds", $saldo_atual, $codigo_cartao);
    $stmt3->execute();

    $_SESSION['flash'] = [
        'mensagem' => 'Atribuição editada com sucesso!',
        'tipo' => 'sucesso'
    ];

    header("Location: cartoes_atribuir.php");
    exit;
}

// ===============================
//   NOVA ATRIBUIÇÃO COM CICLO
// ===============================

// Remove atribuições anteriores do mesmo cartão
$conn->query("UPDATE cartoes_atribuicoes SET ativo = 0 WHERE codigo_cartao = '$codigo_cartao'");

// 1) Criar ciclo
$stmtCiclo = $conn->prepare("
    INSERT INTO cartoes_ciclos
    (codigo_cartao, cpf_funcionario, data_entrega, saldo_inicial, status)
    VALUES (?, ?, NOW(), ?, 'EM_USO')
");
$stmtCiclo->bind_param("ssd", $codigo_cartao, $cpf_funcionario, $saldo_atual);
$stmtCiclo->execute();

$id_ciclo_novo = $stmtCiclo->insert_id;

// 2) Criar nova atribuição com id_ciclo
$stmt5 = $conn->prepare("
    INSERT INTO cartoes_atribuicoes
    (codigo_cartao, cpf_funcionario, saldo_entregue, data_atribuicao, ativo, id_ciclo)
    VALUES (?, ?, ?, NOW(), 1, ?)
");
$stmt5->bind_param("ssdi", 
    $codigo_cartao, 
    $cpf_funcionario, 
    $saldo_atual,
    $id_ciclo_novo
);
$stmt5->execute();

$idAtribuicao = $stmt5->insert_id;

// 3) Atualiza cartão → aguardando assinatura
$stmt6 = $conn->prepare("
    UPDATE cartoes
    SET saldo_atual = ?, 
        status = 'AGUARDANDO ASSINATURA',
        ultima_movimentacao = NOW()
    WHERE codigo_cartao = ?
");
$stmt6->bind_param("ds", $saldo_atual, $codigo_cartao);
$stmt6->execute();

// 4) Salvar ciclo na sessão do funcionário (para registrar gastos)
$_SESSION['id_ciclo'] = $id_ciclo_novo;

$_SESSION['flash'] = [
    'mensagem' => 'Atribuição salva com sucesso!',
    'tipo' => 'sucesso'
];

header("Location: cartoes_atribuir.php");
exit;
?>
