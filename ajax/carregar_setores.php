<?php
session_start();

require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// Verifica login
if (!isset($_SESSION['cpf'])) {
    exit('Acesso negado');
}

// ===============================
// LISTAR LOJAS (FILTRADAS)
// ===============================


$sqlLojas = "
    SELECT id, nome 
    FROM lojas 
    WHERE nome NOT IN ('CAV', 'ESCRITÓRIO', 'CD')
    ORDER BY nome ASC
";
$lojas = $conn->query($sqlLojas);

// Loja selecionada
$lojaId = intval($_GET['loja_id'] ?? 0);

if ($lojaId <= 0) {
    echo "<h3>Selecione uma loja válida</h3>";
    exit;
}

// ===============================
// BUSCAR SETORES PADRÃO
// ===============================
$sqlSetores = "SELECT id, nome_setor FROM setores_padrao ORDER BY nome_setor";
$resSetores = $conn->query($sqlSetores);

// ===============================
// BUSCAR SETORES DA LOJA
// ===============================
$sqlLoja = "SELECT setor_id FROM lojas_setores WHERE loja_id = ?";
$stmt = $conn->prepare($sqlLoja);
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$resLoja = $stmt->get_result();

$setoresAtivos = [];
while ($row = $resLoja->fetch_assoc()) {
    $setoresAtivos[] = intval($row['setor_id']);
}

?>

<link rel="stylesheet" href="/css/form.css">

<div class="controlados-container">

    <h2>⚙️ Configurar Setores da Loja</h2>

    <a href="avaliacoes_loja.php" class="btn btn-cinza">⬅ Voltar</a>

    <div class="bloco">

        <h3>Setores da Loja</h3>

        <?php while ($s = $resSetores->fetch_assoc()): ?>
            <?php $checked = in_array($s['id'], $setoresAtivos) ? 'checked' : ''; ?>

            <div class="item-setor">

                <input type="checkbox"
                    class="check-setor"
                    value="<?= $s['id'] ?>"
                    id="setor_<?= $s['id'] ?>"
                    <?= $checked ?>>

                <label for="setor_<?= $s['id'] ?>">
                    <?= htmlspecialchars($s['nome_setor']) ?>
                </label>

                <button class="btn-editar" data-id="<?= $s['id'] ?>">✏️</button>
                <button class="btn-excluir" data-id="<?= $s['id'] ?>">🗑️</button>

            </div>

        <?php endwhile; ?>

    </div>

</div>
