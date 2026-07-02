<?php
session_start();

require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

header("Content-Type: text/html; charset=utf-8");

// Verifica login
if (!isset($_SESSION['cpf'])) {
    exit("Acesso negado");
}

$cpf = $_SESSION['cpf'];

if (!temAcesso($conn, $cpf, 'ferramentas_auditoria_checklist')) {
    exit("Sem permissão");
}

$lojaId = intval($_GET['loja_id'] ?? 0);

if ($lojaId <= 0) {
    exit("Loja inválida");
}

/*
---------------------------------------------------------
BUSCAR ITENS ATIVOS DA AUDITORIA CHECKLIST
---------------------------------------------------------
*/
$sql = "
    SELECT c.id, c.descricao AS pergunta
    FROM auditoria_checklist_criterios c
    WHERE c.ativo = 1
    ORDER BY c.id
";

$res = $conn->query($sql);

/*
---------------------------------------------------------
GERAR SLIDES NO PADRÃO DA AVALIAÇÃO DE LOJA
---------------------------------------------------------
*/
while ($row = $res->fetch_assoc()):
    $id = $row['id'];
    $pergunta = htmlspecialchars($row['pergunta']);
?>

<div class="carrossel-slide" data-setor-id="<?= $id ?>">

    <h3 class="titulo-setor"><?= $pergunta ?></h3>

    <div class="grupo-botoes">
        <button type="button" class="btn-nota" data-valor="100">SIM</button>
        <button type="button" class="btn-nota" data-valor="50">PARCIAL</button>
        <button type="button" class="btn-nota" data-valor="0">NÃO</button>
    </div>

    <input type="hidden" class="input-nota" value="">

    <div class="criterio-item">
        <p class="criterio-nome">Observação</p>
        <textarea class="obs-setor input-premium" rows="3"></textarea>
    </div>

</div>

<?php endwhile; ?>
