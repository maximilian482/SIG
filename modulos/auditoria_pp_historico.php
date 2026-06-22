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

// Permissão
if (!temAcesso($conn, $cpf, 'ferramentas_auditoria_pp')) {
    $conteudo = "<h2 style='color:red; text-align:center; margin-top:40px;'>❌ Você não tem permissão para acessar o histórico.</h2>";
    include ROOT_PATH . '/includes/layout.php';
    exit;
}

ob_start();
include ROOT_PATH . '/includes/flash.php';
?>

<link rel="stylesheet" href="/css/avaliacoes_base.css">
<link rel="stylesheet" href="/css/auditoria_pp_historico.css">

<div class="botoes-avaliacoes">
    <a href="auditoria_pp.php" class="btn btn-cinza">⬅ Voltar</a>
</div>

<div class="container-avaliacao">
    <div class="avaliacao-wrapper">

        <h2 class="titulo-pagina">📜 Histórico — Auditoria PP</h2>
        <p class="subtitulo-pagina">Veja todas as auditorias realizadas.</p>

        <div class="card-premium lista-avaliacoes-container">

            <!-- FILTROS -->
            <div class="filtros-premium">

                <select id="filtro_loja" class="input-premium">
                    <option value="">Todas as lojas</option>
                    <?php
                        $sqlLojas = "SELECT id, nome FROM lojas ORDER BY nome ASC";
                        $resLojas = $conn->query($sqlLojas);
                        while ($l = $resLojas->fetch_assoc()) {
                            echo "<option value='{$l['id']}'>{$l['nome']}</option>";
                        }
                    ?>
                </select>


                <input type="date" id="filtro_data_ini" class="input-premium">
                <input type="date" id="filtro_data_fim" class="input-premium">

                <button id="btnFiltrar" class="btn btn-azul">Filtrar</button>
            </div>

            <!-- TABELA -->
            <table class="tabela-premium" id="tabela-historico">
                <thead>
                    <tr>
                        <th>Loja</th>
                        <th>Nota Geral</th>
                        <th>Data</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="lista-historico"></tbody>
            </table>

            <!-- PAGINAÇÃO -->
            <div class="paginacao-premium">
                <button id="btnAnterior" class="btn btn-cinza">Anterior</button>
                <span id="paginaAtual">1</span>
                <button id="btnProximo" class="btn btn-cinza">Próximo</button>
            </div>

        </div>

    </div>
</div>

<script src="/js/auditoria_pp_historico.js?v=<?= time() ?>"></script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
