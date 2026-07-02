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
if (!temAcesso($conn, $cpf, 'auditoria_checklist')) {
    $conteudo = "<h2 style='color:red; text-align:center; margin-top:40px;'>❌ Você não tem permissão para acessar Auditoria Checklist.</h2>";
    include ROOT_PATH . '/includes/layout.php';
    exit;
}

$id = intval($_GET["id"]);

ob_start();
?>

<link rel="stylesheet" href="/css/auditoria_checklist.css">

<div class="botoes-avaliacoes">
    <a href="auditoria_checklist_historico.php" class="btn btn-cinza">⬅ Voltar</a>
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
<script src="/js/auditoria_checklist_grafico.js?v=<?= time() ?>"></script>

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

    const resp = await fetch("/ajax/auditoria_checklist_detalhes.php?id=" + id);
    const dados = await resp.json();

    if (dados.erro) {
        container.innerHTML = `<p style="color:red;">${dados.erro}</p>`;
        return;
    }

    const aud = dados.avaliacao;
    const setores = dados.setores || [];

    const notaGeral = parseFloat(aud.nota_geral);

    container.innerHTML = `
        <h3>${aud.loja}</h3>

        <p><strong>Data:</strong> ${new Date(aud.data_avaliacao).toLocaleDateString("pt-BR")}</p>
        <p><strong>Responsável:</strong> ${aud.responsavel_nome}</p>
        <p><strong>Avaliador:</strong> ${aud.avaliador_nome}</p>
        <p><strong>Nota geral:</strong> ${notaGeral}%</p>

        <canvas id="grafico-geral" width="220" height="220" style="margin-top:20px;"></canvas>
        <div id="grafico-geral-texto" class="grafico-texto"></div>

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
    montarGraficoGeral(setores.map(s => ({ valor: s.nota_setor })));

    // Itens
    const itensDiv = document.getElementById("itens-detalhes");

    setores.forEach(i => {

        itensDiv.innerHTML += `
            <div class="barra-setor setor-item" style="margin-bottom:15px;">
                <div class="barra-label"><strong>${i.setor}</strong></div>
                <div class="barra barra-bom" style="width:${i.nota_setor}%;">
                    <span class="barra-nota">${i.nota_setor}%</span>
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
