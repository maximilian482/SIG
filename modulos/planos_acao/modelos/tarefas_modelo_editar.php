<?php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';
require_once __DIR__ . '/../../../config/bootstrap.php';

$conn = conectar();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die("Modelo inválido.");

$sql = "SELECT * FROM tarefas_modelo WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$modelo = $stmt->get_result()->fetch_assoc();

if (!$modelo) die("Modelo não encontrado.");

// Carregar setores
$sqlSetores = "SELECT id, nome FROM setores ORDER BY nome";
$setores = $conn->query($sqlSetores)->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $ativo = intval($_POST['ativo']);
    $id_setor = intval($_POST['id_setor'] ?? 0);

    $sql = "UPDATE tarefas_modelo
            SET titulo = ?, descricao = ?, ativo = ?, id_setor = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiii", $titulo, $descricao, $ativo, $id_setor, $id);
    $stmt->execute();

    header("Location: tarefas_modelo_listar.php");
    exit;
}

include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/menu.php';
include ROOT_PATH . '/perfil/menu_perfil.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Editar Modelo</title>

<style>
form {
    max-width: 600px;
    margin: 20px auto;
}

label {
    font-weight: bold;
    margin-top: 10px;
    display: block;
}

input[type=text], textarea, select {
    width: 100%;
    padding: 8px;
    border-radius: 6px;
    border: 1px solid #ccc;
}

.btn {
    margin-top: 15px;
    padding: 10px 18px;
    background: #006437;
    color: white;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
}

.top-actions {
    display: flex;
    gap: 10px;
    margin: 20px 0;
}

.btn-action {
    padding: 8px 14px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    font-size: 14px;
    transition: 0.2s ease;
}

.btn-action.secondary {
    background: #00A859;
    color: white;
}

.btn-action.secondary:hover {
    background: #00c96b;
}
</style>

</head>
<body>

<h1>Editar Modelo de Tarefa</h1>
<div class="top-actions">
    <a href="tarefas_modelo_listar.php" class="btn-action secondary">
        ← Voltar para Modelos
    </a>
</div>

<form method="post">

    <label>Título</label>
    <input type="text" name="titulo" value="<?= htmlspecialchars($modelo['titulo']); ?>" required>

    <label>Descrição</label>
    <textarea name="descricao" rows="5"><?= htmlspecialchars($modelo['descricao']); ?></textarea>

    <label>Setor sugerido</label>
    <select name="id_setor">
        <option value="">— Selecionar setor —</option>
        <?php foreach ($setores as $s): ?>
            <option value="<?= $s['id'] ?>"
                <?= ($modelo['id_setor'] == $s['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Status</label>
    <select name="ativo">
        <option value="1" <?= $modelo['ativo'] ? 'selected' : ''; ?>>Ativo</option>
        <option value="0" <?= !$modelo['ativo'] ? 'selected' : ''; ?>>Inativo</option>
    </select>

    <button class="btn">Salvar Alterações</button>
</form>

</body>
</html>
