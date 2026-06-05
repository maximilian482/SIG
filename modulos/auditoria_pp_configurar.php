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

if (!temAcesso($conn, $cpf, 'auditoria_pp')) {
    $conteudo = "<h2 style='color:red; text-align:center; margin-top:40px;'>❌ Você não tem permissão para acessar Auditoria PP.</h2>";
    include ROOT_PATH . '/includes/layout.php';
    exit;
}

ob_start();
include ROOT_PATH . '/includes/flash.php';
?>

<link rel="stylesheet" href="/css/auditoria_pp_configurar.css">

<div class="container-setores">

    <a href="/modulos/auditoria_pp.php" class="btn-voltar-premium">⬅ Voltar</a><br><br>

    <h2 class="titulo-pagina">🛡️ Configurar Itens da Auditoria PP</h2>
    <p class="subtitulo-pagina">Ative, desative, edite ou crie novos itens para cada loja.</p>

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

            <p class="hint-premium">Os itens serão carregados automaticamente.</p>
        </div>
    </div>

    <!-- Lista de itens -->
    <div id="itens-container" class="card-premium oculto">
        <h3 class="card-titulo">📋 Itens da Loja</h3>

        <div class="card-conteudo">

            <div id="lista-itens" class="lista-setores">
                <!-- Itens via AJAX -->
            </div>

            <!-- Criar novo item -->
            <div class="novo-setor-box">
                <label class="label-premium">Criar novo item:</label>

                <div class="novo-setor-linha">
                    <input type="text" id="novo_item" class="input-premium" placeholder="Ex: Conferência de bolsas">
                    <button type="button" id="btn-adicionar-item" class="btn-add-premium">➕ Adicionar</button>
                </div>

                <p class="hint-premium">O novo item ficará disponível para todas as lojas.</p>
            </div>

            <button id="btn-salvar-itens" class="btn-submit-premium">💾 Salvar Configurações</button>

        </div>
    </div>

</div>

<script src="/js/auditoria_pp_configurar.js?v=<?= time() ?>"></script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
