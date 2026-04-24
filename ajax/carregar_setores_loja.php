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
// BUSCAR SETORES DA LOJA
// ===============================
$sql = "
    SELECT sp.id AS setor_id, sp.nome_setor
    FROM lojas_setores ls
    INNER JOIN setores_padrao sp ON sp.id = ls.setor_id
    WHERE ls.loja_id = ?
    ORDER BY sp.nome_setor
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$resSetores = $stmt->get_result();

// Critérios fixos
$criterios = [
    "Preço",
    "Exposição",
    "Limpeza",
    "Organização"
];

$slideIndex = 0;

while ($setor = $resSetores->fetch_assoc()):
    $setorId = $setor['setor_id'];
    $nomeSetor = htmlspecialchars($setor['nome_setor']);
?>

<div class="carrossel-slide" data-setor-id="<?= $setorId ?>">

    <h3 class="titulo-setor"><?= $nomeSetor ?></h3>

    <div class="criterios-lista">

        <?php foreach ($criterios as $critNome): ?>
            <div class="criterio-item">

                <p class="criterio-nome"><?= $critNome ?></p>

                <div class="criterio-botoes">
                    <button type="button" class="btn-nota" data-valor="100">SIM</button>
                    <button type="button" class="btn-nota" data-valor="50">PARCIAL</button>
                    <button type="button" class="btn-nota" data-valor="0">NÃO</button>
                </div>

                <input type="hidden"
                       class="input-nota"
                       value="">
            </div>
        <?php endforeach; ?>

        <!-- Observação -->
        <div class="criterio-item">
            <p class="criterio-nome">Observação</p>
            <textarea class="obs-setor input-premium"
                      rows="3"
                      placeholder="Digite observações sobre este setor"></textarea>
        </div>

    </div>

    <!-- Nota automática do setor -->
    <input type="hidden" class="nota-setor-auto" value="">

</div>

<?php
$slideIndex++;
endwhile;
?>
