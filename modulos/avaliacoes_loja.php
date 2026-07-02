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

// Permissão específica da ferramenta
if (!temAcesso($conn, $cpf, 'avaliacoes_loja')) {
    $conteudo = "<h2 style='color:red; text-align:center; margin-top:40px;'>❌ Você não tem permissão para acessar Avaliações de Loja.</h2>";
    include ROOT_PATH . '/includes/layout.php';
    exit;
}

ob_start();
include ROOT_PATH . '/includes/flash.php';
?>

<link rel="stylesheet" href="/css/avaliacoes_base.css">


<div class="botoes-avaliacoes">
    <a href="ferramentas.php" class="btn btn-cinza">⬅ Voltar</a>

    <a id="btn-configurar" href="#" class="btn btn-azul">
        ⚙️ Configurar
    </a>

    <a href="avaliacoes_loja_historico.php" class="btn btn-amarelo">
        📜 Histórico
    </a>
</div>

<div class="container-avaliacao">
    <div class="avaliacao-wrapper">

        <h2 class="titulo-pagina">🏪 Avaliação de Loja</h2>
        <p class="subtitulo-pagina">Selecione a loja e avance pelos setores para concluir a avaliação.</p>

        <!-- Seleção inicial -->
        <div class="card-premium" id="card-selecao-inicial">
            <h3 class="card-titulo">Selecionar Loja</h3>
            <div class="card-conteudo">

                <label for="item_id" class="label-premium">Loja:</label>
                <select id="item_id" class="select-premium">
                    <option value="">— Selecione uma loja —</option>

                    <?php
                    $sql = "SELECT id, nome FROM lojas ORDER BY nome";
                    $res = $conn->query($sql);
                    while ($l = $res->fetch_assoc()):
                    ?>
                        <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['nome']) ?></option>
                    <?php endwhile; ?>
                </select>

                <p class="hint-premium">Os setores serão carregados automaticamente.</p>
            </div>
        </div>

        <!-- Carrossel -->
        <div id="setores-container" class="card-premium oculto">
            <h3 class="card-titulo">🧩 Avaliação por Setor</h3>

            <form id="form-avaliacao" onsubmit="return false;">

                <input type="hidden" id="item_id_hidden">
                <input type="hidden" id="avaliador_id" value="<?= $_SESSION['funcionario_id'] ?>">

                <div id="carrossel-avaliacao" class="carrossel-container">

                    <!-- Slides dinâmicos serão carregados pelo JS específico -->

                    <!-- Slide de resumo -->
                    <div id="slide-resumo" class="carrossel-slide oculto">
                        <h3 class="titulo-final">Resumo da Avaliação</h3>

                        <div class="legenda-avaliacao">
                            <div><span class="legenda-bolinha ruim"></span> Ruim</div>
                            <div><span class="legenda-bolinha parcial"></span> Parcial</div>
                            <div><span class="legenda-bolinha bom"></span> Bom</div>
                        </div>

                        <div id="grafico-setores"></div>

                        <h4 class="titulo-final" style="margin-top:30px;">Avaliação Geral</h4>
                        <canvas id="grafico-geral" width="220" height="220"></canvas>
                    </div>

                    <!-- Slide final -->
                    <div id="slide-final" class="carrossel-slide oculto">

                        <h3 class="titulo-final">Finalizar Avaliação</h3>

                        <div class="grupo-campo">
                            <label class="label-premium">Responsável pela avaliação:</label>
                            <input type="text" id="responsavel_nome" class="input-premium" required>
                        </div>

                        <div class="grupo-campo">
                            <button type="button" id="btn-add-observacao" class="btn-nav-premium" style="background:#777;">
                                + Adicionar observações gerais
                            </button>

                            <div id="obs-wrapper" class="oculto" style="margin-top:15px;">
                                <label class="label-premium">Observações gerais:</label>
                                <textarea id="observacao_final" class="input-premium" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="grupo-campo">
                            <label class="label-premium">Data da avaliação:</label>
                            <input type="date" id="data_avaliacao" class="input-premium">
                        </div>

                        <div class="grupo-campo assinatura-box">
                            <label class="label-premium">Assinatura do responsável:</label>

                            <canvas id="signature-pad" class="signature-canvas"></canvas>

                            <button type="button" class="btn-limpar" onclick="limparAssinatura()">Limpar</button>

                            <input type="hidden" id="assinatura_base64">
                        </div>

                    </div>

                </div>

                <div id="carrossel-nav" class="carrossel-nav oculto">
                    <button type="button" id="btn-voltar" class="btn-nav-premium">⬅ Voltar</button>
                    <button type="button" id="btn-avancar" class="btn-nav-premium">Avançar ➜</button>
                </div>

            </form>
        </div>

        <!-- Últimas avaliações -->
        <div id="ultimas-avaliacoes" class="card-premium lista-avaliacoes-container">
            <br><br><h3 class="card-titulo" align="center">10 Últimas Avaliações</h3>
            
            <div class="table-responsive">
            <table class="tabela-premium" id="tabela-avaliacoes">
                <thead>
                    <tr>
                        <th>Loja</th>
                        <th>Nota Geral</th>
                        <th>Data</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="lista-avaliacoes"></tbody>
            </table>
            </div>
        </div>

    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- SignaturePad -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

<!-- Motores base -->
<script src="/js/avaliacoes_base.js?v=<?= time() ?>"></script>
<script src="/js/avaliacoes_base_graficos.js?v=<?= time() ?>"></script>
<script src="/js/avaliacoes_loja_detalhes.js"></script>


<!-- JS específico da ferramenta -->
<script src="/js/avaliacoes_loja.js?v=<?= time() ?>"></script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
