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
    <a href="avaliacoes_loja.php" class="btn btn-cinza">⬅ Voltar</a>
</div>

<div class="container-avaliacao">
    <div class="avaliacao-wrapper">

        <h2 class="titulo-pagina">📜 Histórico de Avaliações de Loja</h2>
        <p class="subtitulo-pagina">Veja todas as avaliações realizadas.</p>

        <!-- ============================
             FILTROS
        ============================= -->
        <form method="GET" class="filtros-premium">

            <select name="loja" class="select-premium">
                <option value="">Todas as lojas</option>
                <?php
                $resLojas = $conn->query("SELECT id, nome FROM lojas ORDER BY nome");
                while ($l = $resLojas->fetch_assoc()):
                ?>
                    <option value="<?= $l['id'] ?>" <?= ($_GET['loja'] ?? '') == $l['id'] ? 'selected' : '' ?>>
                        <?= $l['nome'] ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <input type="date" name="data_ini" class="input-premium" value="<?= $_GET['data_ini'] ?? '' ?>">
            <input type="date" name="data_fim" class="input-premium" value="<?= $_GET['data_fim'] ?? '' ?>">

            <input type="number" name="nota_min" class="input-premium" placeholder="Nota mínima" value="<?= $_GET['nota_min'] ?? '' ?>">

            <button class="btn-submit-premium">Filtrar</button>

        </form>

        <!-- ============================
             TABELA (MESMO HTML DO PRINCIPAL)
        ============================= -->
        <div class="card-premium lista-avaliacoes-container">

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

            <!-- PAGINAÇÃO -->
            <div class="paginacao">
                <?php
                $pagina = $_GET['p'] ?? 1;
                $pagina = max(1, intval($pagina));
                ?>
                <?php if ($pagina > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['p' => $pagina - 1])) ?>" class="btn-pagina">⬅ Anterior</a>
                <?php endif; ?>

                <a href="?<?= http_build_query(array_merge($_GET, ['p' => $pagina + 1])) ?>" class="btn-pagina">Próxima ➡</a>
            </div>

        </div>

    </div>
</div>

<!-- JS DO MÓDULO PRINCIPAL (MOTOR DOS DETALHES) -->
<script src="/js/avaliacoes_loja_detalhes.js?v=<?= time() ?>"></script>

<!-- JS PARA CARREGAR O HISTÓRICO -->
<script>
function carregarAvaliacoesHistorico() {

    const params = new URLSearchParams(window.location.search);

    fetch('/ajax/avaliacoes_loja_historico_lista.php?' + params.toString())
        .then(res => res.json())
        .then(lista => {

            const tbody = document.getElementById('lista-avaliacoes');
            tbody.innerHTML = '';

            lista.forEach(a => {

                // Linha principal
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${a.loja}</td>
                    <td class="${a.classeNota}">${a.nota}%</td>
                    <td>${a.data}</td>
                    <td class="col-acoes">
                        <a class="btn-icone btn-detalhes" data-id="${a.id}"></a>
                        <a class="btn-icone btn-excluir" data-id="${a.id}"></a>
                    </td>
                `;

                // Linha de detalhes
                const trDetalhes = document.createElement('tr');
                trDetalhes.className = 'linha-detalhes oculto';
                trDetalhes.innerHTML = `
                    <td colspan="4">
                        <div class="detalhes-conteudo"></div>
                    </td>
                `;

                tbody.appendChild(tr);
                tbody.appendChild(trDetalhes);
            });
        });
}

carregarAvaliacoesHistorico();
</script>


<!-- ==========================================================
     JS DE TESTE — DETALHES SIMPLES (FORÇADO)
========================================================== -->
<script>
document.addEventListener("click", function(e) {

    console.log("CLIQUE NA PÁGINA:", e.target);

    const btn = e.target.closest(".btn-detalhes");
    if (!btn) {
        console.log("NÃO É BOTÃO DE DETALHES");
        return;
    }

    console.log("CLICOU NO DETALHES!", btn);

    const id = btn.dataset.id;
    const linha = btn.closest("tr").nextElementSibling;
    const conteudo = linha.querySelector(".detalhes-conteudo");

    if (!linha.classList.contains("oculto")) {
        linha.classList.add("oculto");
        conteudo.innerHTML = "";
        return;
    }

    fetch(`/ajax/avaliacoes_loja_detalhes.php?id=${id}`)
        .then(res => res.text())
        .then(html => {
            conteudo.innerHTML = html;
            linha.classList.remove("oculto");
        });
});
</script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
