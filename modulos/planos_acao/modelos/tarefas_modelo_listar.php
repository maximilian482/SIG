<?php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';
require_once __DIR__ . '/../../../config/bootstrap.php';

$conn = conectar();

include ROOT_PATH . '/includes/head.php';
include ROOT_PATH . '/includes/menu.php';
include ROOT_PATH . '/perfil/menu_perfil.php';

/* ============================================================
   FILTROS
============================================================ */
$filtroSetor = intval($_GET['setor'] ?? 0);
$filtroTitulo = trim($_GET['titulo'] ?? '');

/* ============================================================
   Carregar setores
============================================================ */
$sqlSetores = "SELECT id, nome FROM setores ORDER BY nome";
$setores = $conn->query($sqlSetores)->fetch_all(MYSQLI_ASSOC);

/* ============================================================
   Buscar modelos com JOIN + filtros
============================================================ */
$sql = "
    SELECT tm.*, s.nome AS setor_nome
    FROM tarefas_modelo tm
    LEFT JOIN setores s ON s.id = tm.id_setor
    WHERE 1
";

$params = [];
$types = "";

// Filtro por setor
if ($filtroSetor > 0) {
    $sql .= " AND tm.id_setor = ? ";
    $params[] = $filtroSetor;
    $types .= "i";
}

// Filtro por título
if ($filtroTitulo !== "") {
    $sql .= " AND tm.titulo LIKE ? ";
    $params[] = "%$filtroTitulo%";
    $types .= "s";
}

$sql .= " ORDER BY s.nome, tm.titulo ASC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$modelos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Modelos de Tarefa</title>

<style>
h1 {
    color: #006437;
    border-left: 6px solid #00A859;
    padding-left: 10px;
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

.btn-action.primary {
    background: #006437;
    color: white;
}

.btn-action.primary:hover {
    background: #008f4f;
}

.btn-action.secondary {
    background: #00A859;
    color: white;
}

.btn-action.secondary:hover {
    background: #00c96b;
}

.table-wrapper {
    max-width: 1000px;
    margin: 0 auto;
}

.tabela {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.tabela th, .tabela td {
    border: 1px solid #ddd;
    padding: 8px 10px;
}

.tabela th {
    background: #f0f0f0;
}

.acoes {
    display: flex;
    gap: 8px;
}

.btn-acao {
    padding: 6px 10px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: 0.2s ease;
}

.btn-acao.editar { background: #0077cc; color: white; }
.btn-acao.editar:hover { background: #0090ff; }

.btn-acao.excluir { background: #cc0000; color: white; }
.btn-acao.excluir:hover { background: #e60000; }

.filtros {
    max-width: 1000px;
    margin: 20px auto;
    display: flex;
    gap: 15px;
}

.filtros input, .filtros select {
    padding: 8px;
    border-radius: 6px;
    border: 1px solid #ccc;
    width: 100%;
}
</style>

</head>
<body>

<h1>Modelos de Tarefa</h1>

<div class="top-actions">
    <a href="../planos_acao_listar.php" class="btn-action secondary">
        ← Voltar para Planos de Ação
    </a>

    <a href="tarefas_modelo_criar.php" class="btn-action primary">
        + Criar Modelo de Tarefa
    </a>
</div>

<!-- 🔥 FILTROS -->
<form method="get" class="filtros">
    <select name="setor">
        <option value="">— Todos os setores —</option>
        <?php foreach ($setores as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $filtroSetor == $s['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input type="text" name="titulo" placeholder="Buscar por título..."
           value="<?= htmlspecialchars($filtroTitulo) ?>">

    <button class="btn-action primary">Filtrar</button>

    <a href="tarefas_modelo_listar.php" class="btn-action secondary">
        Limpar filtros
    </a>
</form>

<div class="table-wrapper">
<table class="tabela">
    <thead>
        <tr>
            <th>Título</th>
            <th>Setor sugerido</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>

    <?php foreach ($modelos as $m): ?>
        <tr>
            <td><?= htmlspecialchars($m['titulo']); ?></td>

            <td><?= htmlspecialchars($m['setor_nome'] ?? '—'); ?></td>

            <td class="acoes">
                <a href="tarefas_modelo_editar.php?id=<?= $m['id']; ?>" class="btn-acao editar">
                    ✏️ Editar
                </a>

                <a href="tarefas_modelo_excluir.php?id=<?= $m['id']; ?>"
                   onclick="return confirm('Excluir este modelo?');"
                   class="btn-acao excluir">
                    🗑️ Excluir
                </a>
            </td>
        </tr>
    <?php endforeach; ?>

    </tbody>
</table>
</div>

</body>
</html>
