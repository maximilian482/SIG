<?php
session_start();
require_once '../dados/conexao.php';

$conn = conectar();
if (!$conn) {
    $_SESSION['flash'] = [
        'mensagem' => '❌ Falha ao conectar ao banco de dados.',
        'tipo'     => 'erro'
    ];
    header('Location: funcionarios_inativos.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: funcionarios_inativos.php');
    exit;
}

// ===============================
// CAPTURA DOS DADOS
// ===============================
$id           = intval($_POST['id'] ?? 0);
$lojaOriginal = intval($_POST['loja_original'] ?? 0);

$codigo = trim($_POST['codigo'] ?? '');
$cc     = trim($_POST['cc'] ?? '');

if ($codigo === '' || $codigo === null) $codigo = '0';
if ($cc === '' || $cc === null) $cc = '0';

$nome        = trim($_POST['nome'] ?? 'Funcionário');
$endereco    = trim($_POST['endereco'] ?? '');
$cpf         = preg_replace('/\D/', '', $_POST['cpf'] ?? '');

$cargo_id    = intval($_POST['cargo_id'] ?? 0);
$loja_id     = intval($_POST['loja_id'] ?? 0);
$setor_id    = intval($_POST['setor_id'] ?? 0);

$email       = trim($_POST['email'] ?? '');
$contratacao = $_POST['contratacao'] ?? '';
$nascimento  = $_POST['aniversario'] ?? null;
$telefone    = trim($_POST['telefone'] ?? '');

// ===============================
// VALIDAÇÕES
// ===============================
$erros = [];

if ($id <= 0) $erros[] = '❌ Funcionário inválido.';
if ($nome === '') $erros[] = '❌ Nome é obrigatório.';
if ($cpf === '') $erros[] = '❌ CPF é obrigatório.';
if ($cargo_id <= 0) $erros[] = '❌ Cargo é obrigatório.';
if ($loja_id <= 0) $erros[] = '❌ Loja é obrigatória.';
if ($setor_id <= 0) $erros[] = '❌ Setor é obrigatório.';
if ($contratacao === '') $erros[] = '❌ Data de contratação é obrigatória.';

// ===============================
// VERIFICA DUPLICIDADE
// ===============================
function existeDuplicado($conn, $campo, $valor, $idAtual) {
    if ($valor === '') return false;

    $sql = "SELECT id FROM funcionarios WHERE $campo = ? AND id <> ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $valor, $idAtual);
    $stmt->execute();
    $stmt->store_result();
    $duplicado = $stmt->num_rows > 0;
    $stmt->close();

    return $duplicado;
}

if (existeDuplicado($conn, 'cpf', $cpf, $id)) {
    $erros[] = '❌ Já existe um funcionário com este CPF.';
}

if ($codigo !== '0' && existeDuplicado($conn, 'codigo', $codigo, $id)) {
    $erros[] = '❌ Já existe um funcionário com este Código Vetor.';
}

if ($cc !== '0' && existeDuplicado($conn, 'cc', $cc, $id)) {
    $erros[] = '❌ Já existe um funcionário com este Código CC.';
}

if (!empty($erros)) {
    $_SESSION['flash'] = [
        'mensagem' => implode("<br>", $erros),
        'tipo'     => 'erro'
    ];
    header('Location: funcionarios_inativos.php');
    exit;
}

// ===============================
// ATUALIZAÇÃO NO BANCO
// ===============================
$sql = "
    UPDATE funcionarios SET
        codigo = ?, cc = ?, nome = ?, endereco = ?, cpf = ?,
        cargo_id = ?, loja_id = ?, id_setor = ?, email = ?, contratacao = ?,
        nascimento = ?, telefone = ?, desligamento = NULL
    WHERE id = ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    $_SESSION['flash'] = [
        'mensagem' => '❌ Erro ao preparar atualização: ' . $conn->error,
        'tipo'     => 'erro'
    ];
    header('Location: funcionarios_inativos.php');
    exit;
}

$stmt->bind_param(
    'sssssiisssssi',
    $codigo, $cc, $nome, $endereco, $cpf,
    $cargo_id, $loja_id, $setor_id, $email, $contratacao,
    $nascimento, $telefone, $id
);

if ($stmt->execute()) {

    $_SESSION['flash'] = [
        'mensagem' => '✔ Funcionário <strong>' . htmlspecialchars($nome) . '</strong> foi reativado com sucesso.',
        'tipo'     => 'sucesso'
    ];

    $stmt->close();
    header('Location: funcionarios.php');
    exit;

} else {

    $_SESSION['flash'] = [
        'mensagem' => '❌ Erro ao reativar funcionário: ' . $stmt->error,
        'tipo'     => 'erro'
    ];

    header('Location: funcionarios_inativos.php');
    exit;
}
?>
