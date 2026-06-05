<?php
require_once '../dados/conexao.php';
$conn = conectar();

if (!isset($_GET['id'])) {
    echo "<p>ID não informado.</p>";
    exit;
}

$id = intval($_GET['id']);

$sql = "
    SELECT a.*, l.nome AS loja
    FROM avaliacoes_loja a
    JOIN lojas l ON l.id = a.loja_id
    WHERE a.id = $id
";

$res = $conn->query($sql);

if (!$res || $res->num_rows === 0) {
    echo "<p>Avaliação não encontrada.</p>";
    exit;
}

$av = $res->fetch_assoc();

echo "<h3>Detalhes da Avaliação</h3>";
echo "<p><strong>Loja:</strong> {$av['loja']}</p>";
echo "<p><strong>Data:</strong> " . date("d/m/Y", strtotime($av['data_avaliacao'])) . "</p>";
echo "<p><strong>Responsável:</strong> {$av['responsavel_nome']}</p>";
echo "<p><strong>Avaliador:</strong> {$av['avaliador_id']}</p>";
echo "<p><strong>Nota Geral:</strong> {$av['nota_geral']}%</p>";

echo "<hr>";

$sql2 = "
    SELECT c.nome AS criterio, r.valor, r.nota, r.observacao
    FROM avaliacoes_respostas r
    JOIN criterios c ON c.id = r.criterio_id
    WHERE r.avaliacao_id = $id
";

$res2 = $conn->query($sql2);

while ($c = $res2->fetch_assoc()) {

    $valor = $c['valor'];
    $texto = "N/A";

    if ($valor == 100) $texto = "SIM";
    else if ($valor == 50) $texto = "PARCIAL";
    else if ($valor == 0) $texto = "NÃO";

    echo "<p><strong>{$c['criterio']}:</strong> {$texto} — Nota: {$c['nota']}%</p>";

    if (!empty($c['observacao'])) {
        echo "<p><em>Obs:</em> {$c['observacao']}</p>";
    }
}
