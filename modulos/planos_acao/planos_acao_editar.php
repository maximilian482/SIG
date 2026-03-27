<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';

$conn = conectar();

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die("Plano não encontrado.");
}

// Buscar dados do plano
$sql = "SELECT * FROM planos_acao WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$plano = $stmt->get_result()->fetch_assoc();

if (!$plano) {
    die("Plano não encontrado.");
}

$erro = '';
$sucesso = '';

// Salvar alterações do plano
if (isset($_POST['salvar'])) {

    $titulo      = trim($_POST['titulo']);
    $descricao   = trim($_POST['descricao']);
    $data_inicio = $_POST['data_inicio'];
    $data_fim    = $_POST['data_fim'];

    if ($titulo === '' || $data_inicio === '') {
        $erro = "Título e data de início são obrigatórios.";
    }

    if ($data_fim && $data_fim < $data_inicio) {
        $erro = "A data de término não pode ser menor que a data de início.";
    }

    if (!$erro) {

        $sql = "
            UPDATE planos_acao
            SET titulo = ?, descricao = ?, data_inicio = ?, data_fim = ?
            WHERE id = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $titulo, $descricao, $data_inicio, $data_fim, $id);
        $stmt->execute();

        // Redireciona para a listagem com mensagem de sucesso
        setFlash('success', 'Plano de Ação clonado com sucesso!');
        header("Location: planos_acao_listar.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Editar Plano de Ação</title>

<style>
body {
    background: #f5f5f5;
    font-family: Arial, sans-serif;
}

.container {
    max-width: 700px;
    margin: 20px auto;
    background: #fff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 0 12px rgba(0,0,0,0.1);
}

h1 {
    color: #006437;
    border-left: 6px solid #00A859;
    padding-left: 10px;
}

label {
    font-weight: bold;
    color: #006437;
}

input, textarea {
    width: 100%;
    padding: 8px;
    border: 2px solid #00A859;
    border-radius: 5px;
    margin-top: 5px;
}

.btn {
    padding: 10px 20px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    margin-top: 15px;
}

.btn-salvar { background: #006437; color: white; }
.btn-cancelar { background: #777; color: white; }

.msg-sucesso { color: green; font-weight: bold; }
.msg-erro { color: red; font-weight: bold; }
</style>

</head>
<body>

<div class="container">

<a href="planos_acao_listar.php" style="text-decoration:none;color:#006437;font-weight:bold;">← Voltar</a>

<h1>Editar Plano de Ação</h1>

<?php if ($erro): ?>
    <p class="msg-erro"><?= $erro ?></p>
<?php endif; ?>

<form method="post">

    <label>Título *</label>
    <input type="text" name="titulo" value="<?= htmlspecialchars($plano['titulo']) ?>" required>

    <br><br>

    <label>Descrição</label>
    <textarea name="descricao"><?= htmlspecialchars($plano['descricao']) ?></textarea>

    <br><br>

    <label>Data de Início *</label>
    <input type="date" name="data_inicio" value="<?= $plano['data_inicio'] ?>" required>

    <br><br>

    <label>Data de Término</label>
    <input type="date" name="data_fim" value="<?= $plano['data_fim'] ?>">

    <button type="submit" name="salvar" class="btn btn-salvar">Salvar Alterações</button>

    <a href="planos_acao_listar.php" class="btn btn-cancelar">Cancelar</a>

</form>

</div>

</body>
</html>
