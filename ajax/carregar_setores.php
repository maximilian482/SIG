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

$lojaId = intval($_GET['loja_id'] ?? 0);

if ($lojaId <= 0) {
    exit('Loja inválida');
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

// ===============================
// GERAR HTML
// ===============================
while ($s = $resSetores->fetch_assoc()):
    $checked = in_array($s['id'], $setoresAtivos) ? 'checked' : '';
?>
    <div class="item-setor">
    <input type="checkbox"
           class="check-setor"
           value="<?= $s['id'] ?>"
           id="setor_<?= $s['id'] ?>"
           <?= $checked ?>>

    <label for="setor_<?= $s['id'] ?>">
        <?= htmlspecialchars($s['nome_setor']) ?>
    </label>

    <!-- Botão editar (aparece no hover) -->
    <button class="btn-editar" data-id="<?= $s['id'] ?>">✏️</button>

    <!-- Botão excluir (sempre visível, mas discreto) -->
    <button class="btn-excluir" data-id="<?= $s['id'] ?>">🗑️</button>
</div>

<?php endwhile; ?>
