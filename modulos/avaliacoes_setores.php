<?php
session_start();

require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// Verifica login
if (!isset($_SESSION['cpf'])) {
    header("Location: /login.php");
    exit;
}

$cpf = $_SESSION['cpf'];
$funcId = $_SESSION['funcionario_id'];

ob_start();
include ROOT_PATH . '/includes/flash.php';
?>

<link rel="stylesheet" href="/css/avaliacoes_setores.css">

<div class="container-setores">
<a href="/modulos/avaliacoes_loja.php" class="btn-voltar-premium">⬅ Voltar</a><br><br>

    <h2 class="titulo-pagina">🧩 Configurar Setores por Loja</h2>
    <p class="subtitulo-pagina">Ative, desative, edite ou crie novos setores para cada loja.</p>

    <!-- Seleção da loja -->
    <div class="card-premium">
        <h3 class="card-titulo">🏪 Selecionar Loja</h3>
        <div class="card-conteudo">
            <label for="loja_id" class="label-premium">Loja:</label>
            <select id="loja_id" class="select-premium">
                <option value="">— Selecione uma loja —</option>

                <?php
                $sqlLojas = "SELECT id, nome FROM lojas ORDER BY nome";
                $resLojas = $conn->query($sqlLojas);

                while ($l = $resLojas->fetch_assoc()):
                ?>
                    <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['nome']) ?></option>
                <?php endwhile; ?>
            </select>

            <p class="hint-premium">Os setores serão carregados automaticamente.</p>
        </div>
    </div>

    <!-- Lista de setores -->
    <div id="setores-container" class="card-premium oculto">
        <h3 class="card-titulo">📦 Setores da Loja</h3>
        <div class="card-conteudo">

            <div id="lista-setores" class="lista-setores">
                <!-- Setores serão carregados via AJAX -->
            </div>

            <!-- Criar novo setor -->
            <div class="novo-setor-box">
                <label class="label-premium">Criar novo setor:</label>
                <div class="novo-setor-linha">
                    <input type="text" id="novo_setor" class="input-premium" placeholder="Ex: Perfumaria Premium">
                    <button type="button" id="btn-adicionar-setor" class="btn-add-premium">➕ Adicionar</button>
                </div>
                <p class="hint-premium">O novo setor ficará disponível para todas as lojas.</p>
            </div>

            <button id="btn-salvar-setores" class="btn-submit-premium">💾 Salvar Configurações</button>
        </div>
    </div>

</div>

<script src="/js/avaliacoes_setores.js"></script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
