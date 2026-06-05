<?php
require_once '../dados/conexao.php';
$conn = conectar();

$lojaId = $_GET['loja_id'] ?? null;

if (!$lojaId) {
    echo "<p>Selecione uma loja.</p>";
    exit;
}

// Carregar itens globais + específicos
$sql = "
    SELECT id, pergunta, loja_id
    FROM auditoria_pp_config
    WHERE (loja_id IS NULL OR loja_id = 0 OR loja_id = '')
       OR loja_id = ?
    ORDER BY loja_id IS NULL DESC, pergunta
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$res = $stmt->get_result();

// Carregar itens ativos da loja
$sqlAtivos = "SELECT item_id FROM auditoria_pp_config_ativos WHERE loja_id = ?";
$stmt2 = $conn->prepare($sqlAtivos);
$stmt2->bind_param("i", $lojaId);
$stmt2->execute();
$resAtivos = $stmt2->get_result();

$ativos = [];
while ($a = $resAtivos->fetch_assoc()) {
    $ativos[] = $a['item_id'];
}

// Montar HTML moderno
while ($i = $res->fetch_assoc()):
    $id = $i['id'];
    $pergunta = htmlspecialchars($i['pergunta']);
    $checked = in_array($id, $ativos) ? "checked" : "";
?>

<div class="setor-item">

    <div class="setor-esquerda">
        <input type="checkbox" class="check-item" value="<?= $id ?>" id="item_<?= $id ?>" <?= $checked ?>>
        <label for="item_<?= $id ?>"><?= $pergunta ?></label>
    </div>

    <div class="setor-direita">
        <button class="btn-edit" data-id="<?= $id ?>">✏️</button>
        <button class="btn-del" data-id="<?= $id ?>">🗑️</button>
    </div>

</div>

<?php endwhile; ?>
