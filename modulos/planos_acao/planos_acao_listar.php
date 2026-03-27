<?php
session_start();
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/bootstrap.php';

$conn = conectar();

/* Atualização automática */
$conn->query("
    UPDATE planos_acao
    SET status = 'atrasada'
    WHERE data_fim IS NOT NULL
      AND DATE(data_fim) < CURDATE()
      AND status = 'ativa'
");

/* Buscar planos */
$sql = "
    SELECT p.*, u.nome AS nome_criador
    FROM planos_acao p
    LEFT JOIN funcionarios u ON u.id = p.criado_por
    ORDER BY COALESCE(p.data_criacao, p.id) DESC
";
$planos = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

/* Contadores de tarefas */
$contadores = [];
$res2 = $conn->query("
    SELECT id_plano,
           COUNT(*) AS total,
           SUM(CASE WHEN status = 'concluida' THEN 1 ELSE 0 END) AS concluidas,
           SUM(CASE WHEN status <> 'concluida' THEN 1 ELSE 0 END) AS pendentes
    FROM tarefas_plano
    GROUP BY id_plano
");
while ($row = $res2->fetch_assoc()) {
    $contadores[$row['id_plano']] = $row;
}

/* Normalização */
function normalize($str) {
    return strtolower(
        str_replace(
            ['á','à','ã','â','é','ê','í','ó','ô','õ','ú','ç','Á','À','Ã','Â','É','Ê','Í','Ó','Ô','Õ','Ú','Ç'],
            ['a','a','a','a','e','e','i','o','o','o','u','c','a','a','a','a','e','e','i','o','o','o','u','c'],
            $str
        )
    );
}

/* Contadores das abas */
$counts = ['ativas'=>0,'concluidas'=>0,'atrasadas'=>0,'todas'=>0];

foreach ($planos as $p) {
    $s = normalize($p['status']);
    $counts['todas']++;

    if ($s === 'ativa') $counts['ativas']++;
    if ($s === 'atrasada') $counts['atrasadas']++;
    if (in_array($s, ['concluida','avaliada'])) $counts['concluidas']++;
}

ob_start();
?>

<div class="container">

    
    <h1>Planos de Ação</h1>

    <div class="small">Use as abas para filtrar planos por status.</div>

    <div class="top-actions">
        <a href="planos_acao_novo.php" class="btn-action primary">+ Novo Plano de Ação</a>
        <a href="modelos/tarefas_modelo_listar.php" class="btn-action secondary">+ Criar Modelo de Tarefa</a>
    </div>

    <div class="tabs">
        <button class="tab" data-filter="todas">Todas (<?= $counts['todas'] ?>)</button>
        <button class="tab" data-filter="ativas" aria-selected="true">Ativas (<?= $counts['ativas'] ?>)</button>
        <button class="tab" data-filter="atrasadas">Atrasadas (<?= $counts['atrasadas'] ?>)</button>
        <button class="tab" data-filter="concluidas">Concluídas (<?= $counts['concluidas'] ?>)</button>
    </div>

    <div class="table-wrapper">
        <table class="tabela">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Período</th>
                    <th>Tarefas</th>
                    <th>Criado por</th>
                    <th>Criado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="planos-body">

<?php foreach ($planos as $p):
    $id = $p['id'];
    $c = $contadores[$id] ?? ['total'=>0,'concluidas'=>0,'pendentes'=>0];

    $status = normalize($p['status']);
    $aba = match($status) {
        'ativa' => 'ativas',
        'atrasada' => 'atrasadas',
        'concluida' => 'concluidas',
        default => 'todas'
    };

    $podeFinalizar = ($c['total'] > 0 && $c['concluidas'] == $c['total']);
?>

<tr data-aba="<?= $aba ?>">
    <td><?= htmlspecialchars($p['titulo']) ?></td>

    <td>
        <?= $p['data_inicio'] ? date('d/m/Y', strtotime($p['data_inicio'])) : '(sem data)' ?>
        <?php if ($p['data_fim']): ?>
            a <?= date('d/m/Y', strtotime($p['data_fim'])) ?>
        <?php endif; ?>
    </td>

    <td>
        Total: <?= $c['total'] ?>
        <span class="badge badge-green">✔ <?= $c['concluidas'] ?></span>
        <span class="badge badge-red">⏳ <?= $c['pendentes'] ?></span>
    </td>

    <td><?= htmlspecialchars(explode(' ', trim($p['nome_criador']))[0]) ?></td>

    <td><?= date('d/m/Y H:i', strtotime($p['data_criacao'])) ?></td>

    <td class="actions">
        <a href="planos_acao_detalhes.php?id=<?= $id ?>" class="action-btn">Ver</a>
        <a href="planos_acao_editar.php?id=<?= $id ?>" class="action-btn">Editar</a>

        <?php if ($podeFinalizar): ?>
            <a href="planos_acao_finalizar.php?id=<?= $id ?>" class="action-btn" style="border-color:#00A859;color:#006437;">Finalizar</a>
        <?php else: ?>
            <button class="action-btn" disabled style="opacity:0.4;">Finalizar</button>
        <?php endif; ?>

        <a href="planos_acao_clonar.php?id=<?= $id ?>" class="action-btn" style="border-color:#006437;color:#006437;">
            Clonar
        </a>


        <form method="POST" action="planos_acao_excluir.php" style="display:inline-block;margin:0;"
              onsubmit="return confirm('Tem certeza que deseja excluir este plano?');">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="action-btn excluir">Excluir</button>
        </form>
    </td>
</tr>

<?php endforeach; ?>

            </tbody>
        </table>
    </div>
</div>

<?php
$conteudo = ob_get_clean();
$cssExtra = "/css/planos_acao_listar.css";
$scripts = "<script src='/js/planos_acao_listar.js'></script>";
include ROOT_PATH . "/includes/layout.php";
