<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// ===============================
// VARIÁVEIS DE SESSÃO
// ===============================
$cpf          = $_SESSION['cpf'];
$funcId       = $_SESSION['funcionario_id'];
$lojaUsuario  = $_SESSION['loja'] ?? 0;

// ===============================
// FILTRO
// ===============================
$loja = $_GET['loja'] ?? '';

// ===============================
// SQL
// ===============================
$sql = "
    SELECT 
        ct.*,
        lo.nome AS origem_nome,
        ld.nome AS destino_nome
    FROM chamados_trilho ct
    LEFT JOIN lojas lo ON lo.id = ct.loja_origem_id
    LEFT JOIN lojas ld ON ld.id = ct.loja_destino_id
    WHERE ct.status = 'entregue'
      AND DATE(ct.assinatura_data) = CURDATE()
";

if ($loja !== '') {
    $sql .= " AND ct.loja_destino_id = " . intval($loja);
}

$sql .= " ORDER BY ct.id DESC";

$res = $conn->query($sql);

// ===============================
// HTML
// ===============================
if ($res->num_rows == 0) {
    echo "<p>Nenhuma entrega finalizada hoje.</p>";
    exit;
}

while ($c = $res->fetch_assoc()):
?>
    <div class='card-trilho'>

        <h4 class='tipo-titulo'><?= htmlspecialchars($c['tipo']) ?></h4><br>

        <div class='card-produto'><?= htmlspecialchars($c['descricao']) ?></div>

        <div class='card-header'>
            <span class='protocolo'><?= htmlspecialchars($c['protocolo']) ?></span>
            <span class='tag-status entregues'>Entregue</span>
        </div>

        <div class='card-body'>
            <p><strong>Liberação:</strong> <?= htmlspecialchars($c['origem_nome']) ?></p>
            <p><strong>Entregue:</strong> <?= htmlspecialchars($c['destino_nome']) ?></p>
        </div>

        <div class='card-actions'>
            <button class='btn-trilho btn-detalhes' data-id='<?= $c['id'] ?>'>Detalhes</button>
        </div>

    </div>

<?php endwhile; ?>
