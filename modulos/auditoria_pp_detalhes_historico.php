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

$id = intval($_GET["id"]);

ob_start();
?>

<link rel="stylesheet" href="/css/auditoria_pp.css">

<div class="botoes-avaliacoes">
    <a href="auditoria_pp_historico.php" class="btn btn-cinza">⬅ Voltar</a>
</div>

<div class="container-avaliacao">
    <div class="avaliacao-wrapper">

        <h2 class="titulo-pagina">📄 Detalhes da Auditoria</h2>
        <p class="subtitulo-pagina">Informações completas da auditoria selecionada.</p>

        <div id="detalhes-container" class="card-premium" style="padding:20px;">
            <h3>Carregando detalhes...</h3>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/js/auditoria_pp_grafico.js?v=<?= time() ?>"></script>

<script>
// ID da auditoria vindo da URL
window.AUDITORIA_ID = <?= $id ?>;
</script>

<script>
// ================================
// CARREGAR DETALHES DA AUDITORIA
// ================================
document.addEventListener("DOMContentLoaded", carregarDetalhesHistorico);

async function carregarDetalhesHistorico() {

    const id = window.AUDITORIA_ID;
    const container = document.getElementById("detalhes-container");

    const resp = await fetch("/modulos/auditoria_pp_detalhes.php?id=" + id);
    const dados = await resp.json();

    const aud = dados.auditoria;
    const itens = dados.itens || [];

    // Converter nota geral
    let notaGeral = parseFloat(aud.nota_geral);
    if (notaGeral == 10) notaGeral = 100;
    else if (notaGeral == 5) notaGeral = 50;
    else notaGeral = 0;

    container.innerHTML = `
        <h3>${aud.loja}</h3>

        <p><strong>Data:</strong> ${new Date(aud.data_auditoria).toLocaleDateString("pt-BR")}</p>
        <p><strong>Responsável:</strong> ${aud.responsavel_nome}</p>
        <p><strong>Avaliador:</strong> ${aud.avaliador}</p>
        <p><strong>Nota geral:</strong> ${notaGeral}%</p>

        <canvas id="grafico-detalhe" width="220" height="220" style="margin-top:20px;"></canvas>

        <h3 style="margin-top:25px;">Itens avaliados</h3>
        <div id="itens-detalhes"></div>

        <h3 style="margin-top:25px;">Assinatura do responsável</h3>
        ${
            aud.assinatura
            ? `<img src="${aud.assinatura}" class="img-assinatura">`
            : "<p>Sem assinatura registrada.</p>"
        }
    `;

    // Montar gráfico
    montarGraficoGeral("grafico-detalhe", notaGeral);

    // Itens
    const itensDiv = document.getElementById("itens-detalhes");

    itens.forEach(i => {

        let nota = 0;
        if (i.resposta == 10) nota = 100;
        else if (i.resposta == 5) nota = 50;

        itensDiv.innerHTML += `
            <div class="barra-setor setor-item" style="margin-bottom:15px;">
                <div class="barra-label"><strong>${i.pergunta}</strong></div>
                <div class="barra barra-bom" style="width:${nota}%;">
                    <span class="barra-nota">${nota}%</span>
                </div>
                ${
                    i.observacao
                    ? `<p><strong>Obs:</strong> ${i.observacao}</p>`
                    : ""
                }
            </div>
        `;
    });
}
</script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
