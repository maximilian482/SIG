/* ================================
   VARIÁVEIS GLOBAIS
================================ */
let dadosOriginais = [];
let dadosFiltrados = [];
let paginaAtual = 1;
const itensPorPagina = 10;
let graficos = {};

/* ================================
   INICIALIZAÇÃO
================================ */
document.addEventListener("DOMContentLoaded", () => {
    carregarAvaliacoes();
    carregarFiliais();

    document.getElementById("filtro-filial").onchange = aplicarFiltros;
});

/* ================================
   CARREGAR DADOS
================================ */
async function carregarAvaliacoes() {
    const res = await fetch("/ajax/avaliacoes_get_todas.php");
    dadosOriginais = await res.json();
    dadosFiltrados = [...dadosOriginais];
    renderTabela();
}

async function carregarFiliais() {
    const res = await fetch("/ajax/avaliacoes_get_lojas.php");
    const filiais = await res.json();

    const select = document.getElementById("filtro-filial");
    select.innerHTML = `<option value="">Todas</option>`;

    filiais.forEach(f => {
        select.innerHTML += `<option value="${f.id}">${f.nome}</option>`;
    });
}

/* ================================
   FILTRO POR FILIAL
================================ */
function aplicarFiltros() {
    const filial = document.getElementById("filtro-filial").value;

    dadosFiltrados = dadosOriginais.filter(item => {
        return filial === "" || item.loja_id == filial;
    });

    paginaAtual = 1;
    renderTabela();
}

/* ================================
   PAGINAÇÃO + TABELA
================================ */
function renderTabela() {
    const inicio = (paginaAtual - 1) * itensPorPagina;
    const fim = inicio + itensPorPagina;
    const pagina = dadosFiltrados.slice(inicio, fim);

    const lista = document.getElementById("lista-historico");
    lista.innerHTML = "";

    pagina.forEach(item => {
        const notaClass = `nota-${item.classificacao}`;

        lista.innerHTML += `
            <tr>
                <td>${item.loja}</td>
                <td><span class="badge ${notaClass}">${item.nota}</span></td>
                <td>${item.data}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-primary" onclick="toggleDetalhes(${item.id})">
                        Detalhes
                    </button>
                </td>
            </tr>

            <tr id="detalhes-${item.id}" class="detalhes-linha" style="display:none;">
                <td colspan="4">
                    <div class="detalhes-container">
                        <div class="detalhes-info" id="conteudo-${item.id}"></div>
                        <div class="detalhes-grafico">
                            <canvas id="grafico-${item.id}"></canvas>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    });

    renderPaginacao();
}

function renderPaginacao() {
    const totalPaginas = Math.ceil(dadosFiltrados.length / itensPorPagina);
    const paginacao = document.getElementById("paginacao");

    paginacao.innerHTML = "";

    for (let i = 1; i <= totalPaginas; i++) {
        paginacao.innerHTML += `
            <li class="page-item ${i === paginaAtual ? "active" : ""}">
                <button class="page-link" onclick="mudarPagina(${i})">${i}</button>
            </li>
        `;
    }
}

function mudarPagina(num) {
    paginaAtual = num;
    renderTabela();
}

/* ================================
   DETALHES + GRÁFICO
================================ */
async function toggleDetalhes(id) {
    const linha = document.getElementById(`detalhes-${id}`);
    const conteudo = document.getElementById(`conteudo-${id}`);

    if (linha.style.display === "table-row") {
        linha.style.display = "none";
        if (graficos[id]) graficos[id].destroy();
        return;
    }

    linha.style.display = "table-row";

    const res = await fetch(`/ajax/avaliacoes_get_detalhes.php?id=${id}`);
    const dados = await res.json();

    conteudo.innerHTML = `
        <h5 class="mb-2">Informações gerais</h5>
        <p><strong>Loja:</strong> ${dados.avaliacao.loja}</p>
        <p><strong>Responsável:</strong> ${dados.avaliacao.responsavel_nome}</p>
        <p><strong>Data:</strong> ${dados.avaliacao.data_avaliacao}</p>
        <p><strong>Nota geral:</strong> ${dados.avaliacao.nota_geral}</p>
        <p><strong>Observações:</strong> ${dados.avaliacao.observacao_final || "Nenhuma"}</p>

        <h5 class="mt-3 mb-2">Setores avaliados</h5>
        ${dados.setores.map(s => `
            <div class="setor-bloco mb-2">
                <div class="setor-header">
                    <span class="setor-nome">${s.setor}</span>
                    <span class="setor-nota">Nota: ${s.nota_setor}</span>
                </div>
                <div class="setor-body">
                    <p><strong>Observação:</strong> ${s.observacao || "Nenhuma"}</p>
                    <p class="mb-1"><strong>Critérios:</strong></p>
                    <ul class="mb-0">
                        ${s.criterios.map(c => `<li>${c.criterio}: ${c.valor}</li>`).join("")}
                    </ul>
                </div>
            </div>
        `).join("")}
    `;

    const ctx = document.getElementById(`grafico-${id}`);

    if (graficos[id]) graficos[id].destroy();

    graficos[id] = new Chart(ctx, {
        type: "doughnut",
        data: {
            labels: ["Ruim", "Parcial", "Bom"],
            datasets: [{
                data: [
                    dados.avaliacao.qtd_ruim,
                    dados.avaliacao.qtd_parcial,
                    dados.avaliacao.qtd_bom
                ],
                backgroundColor: ["#dc3545", "#ffc107", "#198754"]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: "bottom" }
            }
        }
    });
}
