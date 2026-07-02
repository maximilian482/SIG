<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

header('Content-Type: application/json; charset=utf-8');

$cpf = $_SESSION['cpf'] ?? '';
if (!$cpf) {
    echo json_encode(['sucesso' => false, 'erro' => 'Sessão expirada']);
    exit;
}

// Buscar usuário
$stmt = $conn->prepare("SELECT id FROM funcionarios WHERE cpf = ?");
$stmt->bind_param("s", $cpf);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$usuarioId = $usuario['id'] ?? 0;

// Dados recebidos
$id = intval($_POST['id'] ?? 0);
$tipo = $_POST['tipo_compra'] ?? '';

if (!$id || !in_array($tipo, ['com_nota', 'sem_nota'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos']);
    exit;
}

// Buscar solicitação
$stmt = $conn->prepare("SELECT solicitante_id, status FROM compras_externas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$sol = $stmt->get_result()->fetch_assoc();

if (!$sol) {
    echo json_encode(['sucesso' => false, 'erro' => 'Solicitação não encontrada']);
    exit;
}

// Permissão
$usuarioPodeFinalizar =
    $usuarioId == $sol['solicitante_id'] ||
    temAcesso($conn, $cpf, 'super') ||
    temAcesso($conn, $cpf, 'ceo') ||
    temAcesso($conn, $cpf, 'gestao_compras_externas');

if (!$usuarioPodeFinalizar) {
    echo json_encode(['sucesso' => false, 'erro' => 'Sem permissão']);
    exit;
}

// Não pode finalizar duas vezes
if ($sol['status'] === 'concluido') {
    echo json_encode(['sucesso' => false, 'erro' => 'Compra já concluída']);
    exit;
}


// ============================================================
// VALIDAÇÃO DOS CAMPOS OBRIGATÓRIOS
// ============================================================

// COM NOTA
if ($tipo === 'com_nota') {

    if (empty($_POST['numero_nota'])) {
        echo json_encode(['sucesso' => false, 'erro' => 'O número da nota é obrigatório']);
        exit;
    }

    if (empty($_POST['data_compra'])) {
        echo json_encode(['sucesso' => false, 'erro' => 'A data da compra é obrigatória']);
        exit;
    }

    if (empty($_POST['valor'])) {
        echo json_encode(['sucesso' => false, 'erro' => 'O valor da compra é obrigatório']);
        exit;
    }

    if (empty($_POST['local_compra'])) {
        echo json_encode(['sucesso' => false, 'erro' => 'O local da compra é obrigatório']);
        exit;
    }

    // Cupom obrigatório
    if (empty($_FILES['anexos']['name'][0])) {
        echo json_encode(['sucesso' => false, 'erro' => 'É obrigatório anexar o cupom fiscal']);
        exit;
    }
}


// SEM NOTA
if ($tipo === 'sem_nota') {

    if (empty($_POST['data_compra'])) {
        echo json_encode(['sucesso' => false, 'erro' => 'A data da compra é obrigatória']);
        exit;
    }

    if (empty($_POST['hora_ajuste'])) {
        echo json_encode(['sucesso' => false, 'erro' => 'A hora do ajuste é obrigatória']);
        exit;
    }

    if (empty($_POST['quantidade_ajustada'])) {
        echo json_encode(['sucesso' => false, 'erro' => 'A quantidade ajustada é obrigatória']);
        exit;
    }

    // Cupom obrigatório
    if (empty($_FILES['arquivo_cupom']['name'])) {
        echo json_encode(['sucesso' => false, 'erro' => 'É obrigatório anexar o cupom fiscal']);
        exit;
    }

    // Print obrigatório
    if (empty($_FILES['arquivo_print']['name'])) {
        echo json_encode(['sucesso' => false, 'erro' => 'É obrigatório anexar o print da tela de ajuste']);
        exit;
    }
}


// ============================================================
// Atualizar informações
// ============================================================

if ($tipo === 'com_nota') {

    $stmt = $conn->prepare("
        UPDATE compras_externas
        SET tipo_compra = 'com_nota',
            numero_nota = ?,
            data_compra = ?,
            valor = ?,
            local_compra = ?,
            observacoes = ?
        WHERE id = ?
    ");
    $stmt->bind_param(
        "ssdssi",
        $_POST['numero_nota'],
        $_POST['data_compra'],
        $_POST['valor'],
        $_POST['local_compra'],
        $_POST['observacoes'],
        $id
    );

} else {

    $stmt = $conn->prepare("
        UPDATE compras_externas
        SET tipo_compra = 'sem_nota',
            data_compra = ?,
            hora_ajuste = ?,
            quantidade_ajustada = ?,
            observacoes = ?
        WHERE id = ?
    ");
    $stmt->bind_param(
        "ssisi",
        $_POST['data_compra'],
        $_POST['hora_ajuste'],
        $_POST['quantidade_ajustada'],
        $_POST['observacoes'],
        $id
    );
}

$stmt->execute();


// ============================================================
// UPLOAD DE ANEXOS
// ============================================================

$diretorio = ROOT_PATH . '/uploads/compras_externas/';
if (!is_dir($diretorio)) mkdir($diretorio, 0777, true);

// COM NOTA
if ($tipo === 'com_nota') {

    foreach ($_FILES['anexos']['name'] as $i => $nomeOriginal) {

        $tmp = $_FILES['anexos']['tmp_name'][$i];
        $ext = pathinfo($nomeOriginal, PATHINFO_EXTENSION);
        $nomeFinal = "compra_{$id}_" . time() . "_{$i}." . $ext;

        move_uploaded_file($tmp, $diretorio . $nomeFinal);

        $stmt = $conn->prepare("
            INSERT INTO compras_externas_anexos (compra_id, tipo, arquivo, enviado_por, criado_em)
            VALUES (?, 'cupom', ?, ?, NOW())
        ");
        $stmt->bind_param("isi", $id, $nomeFinal, $usuarioId);
        $stmt->execute();
    }
}


// SEM NOTA
if ($tipo === 'sem_nota') {

    // CUPOM
    $nomeOriginal = $_FILES['arquivo_cupom']['name'];
    $tmp = $_FILES['arquivo_cupom']['tmp_name'];
    $ext = pathinfo($nomeOriginal, PATHINFO_EXTENSION);
    $nomeFinal = "compra_{$id}_" . time() . "_cupom." . $ext;

    move_uploaded_file($tmp, $diretorio . $nomeFinal);

    $stmt = $conn->prepare("
        INSERT INTO compras_externas_anexos (compra_id, tipo, arquivo, enviado_por, criado_em)
        VALUES (?, 'cupom', ?, ?, NOW())
    ");
    $stmt->bind_param("isi", $id, $nomeFinal, $usuarioId);
    $stmt->execute();

    // AJUSTE
    $nomeOriginal = $_FILES['arquivo_print']['name'];
    $tmp = $_FILES['arquivo_print']['tmp_name'];
    $ext = pathinfo($nomeOriginal, PATHINFO_EXTENSION);
    $nomeFinal = "compra_{$id}_" . time() . "_ajuste." . $ext;

    move_uploaded_file($tmp, $diretorio . $nomeFinal);

    $stmt = $conn->prepare("
        INSERT INTO compras_externas_anexos (compra_id, tipo, arquivo, enviado_por, criado_em)
        VALUES (?, 'ajuste', ?, ?, NOW())
    ");
    $stmt->bind_param("isi", $id, $nomeFinal, $usuarioId);
    $stmt->execute();
}


// ============================================================
// FINALIZAR COMPRA
// ============================================================

$stmt = $conn->prepare("
    UPDATE compras_externas
    SET status = 'concluido',
        finalizada_em = NOW()
    WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();

echo json_encode(['sucesso' => true]);
exit;
