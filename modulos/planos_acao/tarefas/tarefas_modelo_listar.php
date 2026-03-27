<?php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';

$conn = conectar();

// Buscar todos os modelos ativos
$sql = "SELECT id, titulo, descricao, tipo_responsavel, ativo, criado_em 
        FROM tarefas_modelo 
        ORDER BY titulo ASC";

$modelos = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Modelos de Tarefa</title>

<style>
body {
    background: #f5f5f5;
    font-family: Arial, sans-serif;
}

.container {
    max-width: 900px;
    margin: 30px auto;
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

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

table th {
    background: #006437;
    color: white;
    padding: 10px;
    text-align: left;
}

table td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

.btn {
    background: #006437;
    color: white;
    padding: 8px 14px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    transition: 0.2s;
}

.btn:hover {
    background: #00A859;
}

.btn-small {
    padding: 6px 10px;
    font-size: 14px;
}

.status-ativo {
    color: #006437;
    font-weight: bold;
}

.status-inativo {
    color: #cc0000;
    font-weight: bold;
}

.top-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
</style>

</head>
<body>

<div class="container">

    <div class="top-actions">
        <h1>Modelos de Tarefa</h1>
        <a href="tarefa_criar.php" class="btn">+ Criar Modelo</a>
    </div>

    <?php if (empty($modelos)): ?>
        <p>Nenhum modelo de tarefa cadastrado ainda.</p>
    <?php else: ?>

    <table>
        <tr>
            <th>Título</th>
            <th>Responsável</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($modelos as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['titulo']) ?></td>

                <td>
                    <?php
                        if ($m['tipo_responsavel'] === 'usuario') echo "Usuário";
                        if ($m['tipo_responsavel'] === 'setor')   echo "Setor";
                        if ($m['tipo_responsavel'] === 'loja')    echo "Loja";
                    ?>
                </td>

                <td>
                    <?php if ($m['ativo']): ?>
                        <span class="status-ativo">Ativo</span>
                    <?php else: ?>
                        <span class="status-inativo">Inativo</span>
                    <?php endif; ?>
                </td>

                <td>
                    <a href="tarefa_modelo_editar.php?id=<?= $m['id'] ?>" class="btn btn-small">Editar</a>
                    <a href="tarefa_modelo_toggle.php?id=<?= $m['id'] ?>" class="btn btn-small" 
                       onclick="return confirm('Alterar status deste modelo?');">
                       Ativar/Desativar
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>

    </table>

    <?php endif; ?>

</div>

</body>
</html>
