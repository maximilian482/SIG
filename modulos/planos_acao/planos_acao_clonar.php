<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';

$conn = conectar();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'Plano inválido.');
    header("Location: planos_acao_listar.php");
    exit;
}

// Buscar plano original
$sql = "SELECT * FROM planos_acao WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$plano = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$plano) {
    setFlash('error', 'Plano não encontrado.');
    header("Location: planos_acao_listar.php");
    exit;
}

ob_start();
?>

<div class="container">
    <h1>Clonar Plano de Ação</h1>
    <p class="small">Defina as novas datas antes de concluir a clonagem.</p>

    <form method="POST" action="planos_acao_clonar_salvar.php" class="form-padrao">

        <input type="hidden" name="id_original" value="<?= $plano['id'] ?>">

        <label>Título do novo plano:</label>
        <input type="text" name="titulo" value="<?= htmlspecialchars($plano['titulo']) ?> (Cópia)" required>

        <label>Descrição:</label>
        <textarea name="descricao" required><?= htmlspecialchars($plano['descricao']) ?></textarea>

        <label>Nova data de início:</label>
        <input type="date" name="data_inicio" value="<?= date('Y-m-d') ?>" required>

        <label>Nova data de término:</label>
        <input type="date" name="data_fim" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>

        <div class="botoes-form">
            <button type="submit" class="btn">Salvar</button>
            <a href="planos_acao_listar.php" class="btn-secondary">Cancelar</a>
        </div>

    </form>

</div>

<?php
$conteudo = ob_get_clean();
$cssExtra = "/css/planos_acao_listar.css";
$scripts = "<script src='/js/planos_acao_listar.js'></script>";
include ROOT_PATH . "/includes/layout.php";
