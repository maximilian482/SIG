<?php
session_start();
require_once '../dados/conexao.php';

$conn = conectar();
if (!$conn) {
    $_SESSION['flash'] = [
        'mensagem' => "❌ Falha ao conectar ao banco de dados.",
        'tipo' => 'erro'
    ];
    header("Location: funcionarios.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: funcionarios.php");
    exit;
}

$id = intval($_POST['id'] ?? 0);
$loja_original = intval($_POST['loja_original'] ?? 0);

if ($id <= 0) {
    $_SESSION['flash'] = [
        'mensagem' => "❌ Funcionário inválido.",
        'tipo' => 'erro'
    ];
    header("Location: funcionarios.php");
    exit;
}

// ===============================
// CAPTURAR DADOS
// ===============================
$codigo      = trim($_POST['codigo'] ?? '0');
$cc          = trim($_POST['cc'] ?? '0');
$nome        = trim($_POST['nome'] ?? '');
$endereco    = trim($_POST['endereco'] ?? '');
$cpf         = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$cargo_id    = intval($_POST['cargo_id'] ?? 0);
$funcao_secundaria_id = intval($_POST['funcao_secundaria_id'] ?? 0);
$loja_id     = intval($_POST['loja_id'] ?? 0);
$email       = trim($_POST['email'] ?? '');
$contratacao = $_POST['contratacao'] ?? null;
$nascimento  = $_POST['aniversario'] ?? null;
$telefone    = trim($_POST['telefone'] ?? '');
$id_setor    = intval($_POST['id_setor'] ?? 0);

// ===============================
// VALIDAÇÕES
// ===============================
$erros = [];

if ($nome === '') $erros[] = "❌ O nome é obrigatório.";
if (strlen($cpf) !== 11) $erros[] = "❌ CPF inválido.";
if ($cargo_id <= 0) $erros[] = "❌ Cargo inválido.";
if ($loja_id <= 0) $erros[] = "❌ Loja inválida.";
if ($id_setor <= 0) $erros[] = "❌ Setor inválido.";

// CPF duplicado
$stmt = $conn->prepare("SELECT id FROM funcionarios WHERE cpf = ? AND id != ?");
$stmt->bind_param("si", $cpf, $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) $erros[] = "❌ Já existe outro funcionário com este CPF.";
$stmt->close();

// Código duplicado
if ($codigo !== '0') {
    $stmt = $conn->prepare("SELECT id FROM funcionarios WHERE codigo = ? AND id != ?");
    $stmt->bind_param("si", $codigo, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) $erros[] = "❌ Já existe outro funcionário com este Código Manual.";
    $stmt->close();
}

// CC duplicado
if ($cc !== '0') {
    $stmt = $conn->prepare("SELECT id FROM funcionarios WHERE cc = ? AND id != ?");
    $stmt->bind_param("si", $cc, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) $erros[] = "❌ Já existe outro funcionário com este CC.";
    $stmt->close();
}

if (!empty($erros)) {
    $_SESSION['flash'] = [
        'mensagem' => implode("<br>", $erros),
        'tipo' => 'erro'
    ];
    header("Location: funcionarios_editar.php?id=$id&loja=$loja_original");
    exit;
}

// ===============================
// ATUALIZAR FUNCIONÁRIO
// ===============================
$sql = "
UPDATE funcionarios SET
    codigo = ?, cc = ?, nome = ?, endereco = ?, cpf = ?, cargo_id = ?, loja_id = ?, 
    id_setor = ?, email = ?, contratacao = ?, nascimento = ?, telefone = ?
WHERE id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssssiisssssi",
    $codigo, $cc, $nome, $endereco, $cpf, $cargo_id, $loja_id,
    $id_setor, $email, $contratacao, $nascimento, $telefone, $id
);

if (!$stmt->execute()) {
    $_SESSION['flash'] = [
        'mensagem' => "❌ Erro ao atualizar: " . $stmt->error,
        'tipo' => 'erro'
    ];
    header("Location: funcionarios_editar.php?id=$id&loja=$loja_original");
    exit;
}

// ===============================
// ATUALIZAR FUNÇÃO SECUNDÁRIA
// ===============================

// Remover função secundária atual
$conn->query("DELETE FROM funcionario_funcoes_secundarias WHERE funcionario_id = $id");

// Inserir nova função secundária, se houver
if ($funcao_secundaria_id > 0) {
    $stmtFunc = $conn->prepare("
        INSERT INTO funcionario_funcoes_secundarias (funcionario_id, funcao_secundaria_id)
        VALUES (?, ?)
    ");
    $stmtFunc->bind_param("ii", $id, $funcao_secundaria_id);
    $stmtFunc->execute();
}

// ===============================
// SUCESSO
// ===============================
$_SESSION['flash'] = [
    'mensagem' => "✔ Funcionário <strong>$nome</strong> atualizado com sucesso.",
    'tipo' => 'sucesso'
];

header("Location: funcionarios.php");
exit;
?>
