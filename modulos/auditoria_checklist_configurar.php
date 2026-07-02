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

// Permissão correta
if (!temAcesso($conn, $cpf, 'ferramentas_auditoria_checklist')) {
    $conteudo = "<h2 style='color:red; text-align:center; margin-top:40px;'>❌ Você não tem permissão para acessar esta página.</h2>";
    include ROOT_PATH . '/includes/layout.php';
    exit;
}

ob_start();
include ROOT_PATH . '/includes/flash.php';
?>

<link rel="stylesheet" href="/css/avaliacoes_base.css">
<link rel="stylesheet" href="/css/auditoria_checklist_configurar.css">

<div class="botoes-avaliacoes">
    <a href="auditoria_checklist.php" class="btn-voltar-premium">⬅ Voltar</a>
</div>

<div class="container-avaliacao">
    <div class="avaliacao-wrapper">

        <h2 class="titulo-pagina">⚙️ Configurar Auditoria Checklist</h2>
        <p class="subtitulo-pagina">Ative, edite, exclua ou crie novos itens da auditoria para cada loja.</p>

        <!-- Seleção da loja -->
        <div class="card-premium">
            <h3 class="card-titulo">🏪 Selecionar Loja</h3>
            <div class="card-conteudo">

                <label class="label-premium">Loja:</label>
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
            <h3 class="card-titulo">📦 Itens da Auditoria</h3>

            <div class="card-conteudo">

                <!-- LISTA PREMIUM -->
                <div id="lista-itens" class="lista-premium">
                    <!-- Conteúdo carregado via AJAX -->
                </div>

                <!-- Criar novo item -->
                <div class="novo-item-box">
                    <label class="label-premium">Criar novo item:</label>

                    <div class="novo-item-linha">
                        <input type="text" id="novo_item" class="input-premium" placeholder="Ex: Conferência de validade">
                        <button type="button" id="btn-adicionar-item" class="btn-add-premium">➕ Adicionar</button>
                    </div>

                    <p class="hint-premium">O novo item ficará disponível para todas as lojas.</p>
                </div>

                <!-- Botão salvar -->
                <button type="button" id="btn-salvar-itens" class="btn-submit-premium">💾 Salvar Configurações</button>

            </div>
        </div>

    </div>
</div>

<!-- MODAL EDITAR -->
<div id="modalEditar" class="modal-premium oculto">
    <div class="modal-premium-content">

        <button class="modal-close-btn" id="modalCloseX">×</button>

        <h2 class="modal-premium-titulo">Editar Item</h2>

        <input type="hidden" id="edit_id">

        <label class="label-premium">Pergunta:</label>
        <input type="text" id="edit_pergunta" class="input-premium modal-input">

        <div class="modal-premium-botoes">
            <button id="btnSalvarEdicao" class="btn-premium-salvar">Salvar</button>
            <button id="btnFecharModal" class="btn-premium-cancelar">Cancelar</button>
        </div>
    </div>
</div>

<script src="/js/auditoria_checklist_configurar.js"></script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
