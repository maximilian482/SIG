<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';

$conn = conectar();

$idUsuario = $_SESSION['id_funcionario'] ?? null;
$cpf       = $_SESSION['cpf'] ?? null;

if (!$idUsuario) {
    die("Acesso inválido.");
}

// Buscar setor e loja do usuário
$sqlUser = "
    SELECT setor, loja
    FROM funcionarios
    WHERE id = ?
";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("i", $idUsuario);
$stmtUser->execute();
$dadosUser = $stmtUser->get_result()->fetch_assoc();

$setorUsuario = $dadosUser['setor'] ?? null;
$lojaUsuario  = $dadosUser['loja'] ?? null;

/*
    Lógica:
    O usuário verá APENAS planos ATIVOS onde ele tem pelo menos UMA tarefa,
    seja por:
    - responsavel_usuario = idUsuario
    - responsavel_setor   = setorUsuario
    - responsavel_loja    = lojaUsuario
*/

$sql = "
    SELECT DISTINCT p.id, p.titulo, p.data_inicio, p.data_fim, p.status
    FROM planos_acao p
    INNER JOIN tarefas t ON t.id_plano = p.id
    WHERE p.status = 'ativa'
      AND (
            t.responsavel_usuario = ?
         OR t.responsavel_setor   = ?
         OR t.responsavel_loja    = ?
      )
    ORDER BY p.data_inicio DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isi", $idUsuario, $setorUsuario, $lojaUsuario);
$stmt->execute();
$planos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Meus Planos de Ação</title>

<style>
body {
    background:#f5f5f5;
    font-family:Arial, sans-serif;
}
.container {
    max-width:900px;
    margin:20px auto;
    background:#fff;
    padding:25px;
    border-radius:10px;
    box-shadow:0 0 12px rgba(0,0,0,0.1);
}
h1 {
    color:#006437;
    border-left:6px solid #00A859;
    padding-left:10px;
}
.tabela {
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}
.tabela th, .tabela td {
    padding:10px;
    border-bottom:1px solid #ddd;
}
.btn {
    background:#006437;
    color:white;
    padding:8px 14px;
    border-radius:6px;
    text-decoration:none;
    font-weight:bold;
}
.btn:hover {
    background:#00A859;
}
</style>

</head>
<body>

<div class="container">

<h1>Meus Planos de Ação</h1>

<p>Aqui você encontra todos os planos de ação ativos que possuem tarefas atribuídas a você, ao seu setor ou à sua loja.</p>

<?php if (empty($planos)): ?>
    <p><strong>Nenhum plano de ação disponível para você no momento.</strong></p>
<?php else: ?>

<table class="tabela">
    <tr>
        <th>Título</th>
        <th>Período</th>
        <th>Minhas Tarefas</th>
        <th>Ações</th>
    </tr>

    <?php foreach ($planos as $p): ?>

        <?php
        // Contar tarefas do usuário dentro do plano
        $sqlCount = "
            SELECT COUNT(*) AS total
            FROM tarefas
            WHERE id_plano = ?
              AND (
                    responsavel_usuario = ?
                 OR responsavel_setor   = ?
                 OR responsavel_loja    = ?
              )
        ";
        $stmtC = $conn->prepare($sqlCount);
        $stmtC->bind_param("iiii", $p['id'], $idUsuario, $setorUsuario, $lojaUsuario);
        $stmtC->execute();
        $totalTarefas = $stmtC->get_result()->fetch_assoc()['total'];
        ?>

        <tr>
            <td><?= htmlspecialchars($p['titulo']) ?></td>
            <td>
                <?= date('d/m/Y', strtotime($p['data_inicio'])) ?>
                a
                <?= date('d/m/Y', strtotime($p['data_fim'])) ?>
            </td>
            <td><?= $totalTarefas ?></td>
            <td>
                <a href="meu_plano_detalhes.php?id=<?= $p['id'] ?>" class="btn">Abrir</a>
            </td>
        </tr>

    <?php endforeach; ?>

</table>

<?php endif; ?>

</div>

</body>
</html>
