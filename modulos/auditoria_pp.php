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

if (!temAcesso($conn, $cpf, 'ferramentas_auditoria_pp')) {
    $conteudo = "<h2 style='color:red; text-align:center; margin-top:40px;'>❌ Você não tem permissão para acessar Auditoria PP.</h2>";
    include ROOT_PATH . '/includes/layout.php';
    exit;
}


ob_start();
include ROOT_PATH . '/includes/flash.php';
?>

<link rel="stylesheet" href="/css/auditoria_pp.css">

<div class="botoes-avaliacoes">
    <a href="ferramentas.php" class="btn btn-cinza">⬅ Voltar</a>

    <a href="auditoria_pp_configurar.php" class="btn btn-azul">
        ⚙️ Configurar
    </a>

    <a href="auditoria_pp_historico.php" class="btn btn-amarelo">
        📜 Histórico
    </a>
</div>

<div class="container-avaliacao">
    <div class="avaliacao-wrapper">

        <h2 class="titulo-pagina">🛡️ Auditoria de Prevenção e Perdas</h2>
        <p class="subtitulo-pagina">Selecione a loja e avance pelos itens configurados.</p>

        <!-- Seleção da loja -->
        <div class="card-premium">
            <h3 class="card-titulo">Selecionar Loja</h3>
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

        <!-- Container do carrossel -->
                <div id="itens-container" class="card-premium oculto">
                    <h3 class="card-titulo">🧩 Itens da Auditoria</h3>

                    <form id="form-auditoria" onsubmit="return false;">

                        <input type="hidden" id="loja_id_hidden">
                        <input type="hidden" id="avaliador_id" value="<?= $_SESSION['funcionario_id'] ?>">

                        <!-- Carrossel -->
                        <div id="carrossel-auditoria" class="carrossel-container">

            <!-- Slides serão carregados via JS -->

            <!-- SLIDE DE RESUMO -->
                <div id="slide-resumo" class="carrossel-slide oculto">

                    <h3 class="titulo-final" style="margin-bottom:20px;">Resumo da Auditoria</h3>

                    <div class="legenda-avaliacao">
                        <div><span class="legenda-bolinha ruim"></span> Não</div>
                        <div><span class="legenda-bolinha parcial"></span> Parcial</div>
                        <div><span class="legenda-bolinha bom"></span> Sim</div>
                    </div>

                    <!-- BARRAS HORIZONTAIS POR ITEM -->
                    <div id="lista-resumo-itens"></div>

                    <h4 class="titulo-final" style="margin-top:30px;">Nota Geral</h4>

                    <div class="grafico-geral-wrapper">
                        <canvas id="grafico-geral" width="220" height="220"></canvas>
                        <div id="grafico-geral-texto" class="grafico-geral-texto"></div>
                    </div>

                </div>



            <!-- SLIDE FINAL -->
            <div id="slide-final" class="carrossel-slide oculto">

                <h3 class="titulo-final">Finalizar Auditoria</h3>

                <div class="grupo-campo">
                    <label class="label-premium">Responsável pela auditoria:</label>
                    <input type="text" id="responsavel_nome" class="input-premium" placeholder="Nome completo" required>
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
                    <label class="label-premium">Data da auditoria:</label>
                    <input type="date" id="data_auditoria" class="input-premium">
                </div>

                <div class="grupo-campo assinatura-box">
                    <label class="label-premium">Assinatura do responsável:</label>

                    <canvas id="signature-pad" width="500" height="200" class="signature-canvas"></canvas>

                    <button type="button" class="btn-limpar" onclick="limparAssinatura()">Limpar</button>

                    <input type="hidden" id="assinatura_base64">
                </div>

            </div>

        </div>


                <!-- Navegação -->
                <div id="carrossel-nav" class="carrossel-nav oculto">
                    <button type="button" id="btn-voltar" class="btn-nav-premium">⬅ Voltar</button>
                    <button type="button" id="btn-avancar" class="btn-nav-premium">Avançar ➜</button>
                </div>

            </form>
        </div>

        <!-- Últimas auditorias -->
        <div id="ultimas-auditorias" class="card-premium lista-avaliacoes-container">
            <br><br><h3 class="card-titulo" align="center">10 Últimas Auditorias</h3>
            
            <div class="table-responsive">
                <table class="tabela-premium" id="tabela-auditorias">
                <thead>
                    <tr>
                        <th>Loja</th>
                        <th>Nota Geral</th>
                        <th>Data</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="lista-auditorias"></tbody>
               </table>
            </div>

        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="/js/auditoria_pp_grafico.js?v=<?= time() ?>"></script>
<script src="/js/auditoria_pp.js?v=<?= time() ?>"></script>
<script src="/js/auditoria_pp_detalhes.js?v=<?= time() ?>"></script>




<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
