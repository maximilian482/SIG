// ==========================================================
// GERAR RESUMO (GRÁFICOS + DETALHES)
// ==========================================================

function gerarResumo() {

    const container = document.getElementById("grafico-setores");
    container.innerHTML = "";

    let soma = 0;
    let total = 0;

    document.querySelectorAll(".carrossel-slide[data-setor-id]").forEach(slide => {

        const nome = slide.querySelector(".titulo-setor").innerText;
        const nota = parseFloat(slide.querySelector(".nota-setor-auto").value);
        const obs  = slide.querySelector(".obs-setor")?.value || "";

        // ================================
        // COLETAR CRITÉRIOS
        // ================================
        let criteriosHTML = "";

        slide.querySelectorAll(".criterio-item").forEach(item => {

            const nomeCrit = item.querySelector(".criterio-nome")?.textContent.trim();
            const valor = parseInt(item.querySelector(".input-nota")?.value || -1);

            if (!nomeCrit || nomeCrit === "Observação") return;

            let texto = "N/A";
            if (valor === 100) texto = "SIM";
            else if (valor === 50) texto = "PARCIAL";
            else if (valor === 0) texto = "NÃO";

            criteriosHTML += `
                <div class="criterio-linha">
                    <strong>${nomeCrit}:</strong> ${texto}
                </div>
            `;
        });

        // ================================
        // CASO SEJA N/A
        // ================================
        if (nota === -1) {
            container.innerHTML += `
                <div class="barra-setor setor-item">
                    <div class="barra-label"><strong>${nome}</strong></div>
                    <div class="barra barra-na">N/A</div>
                </div>
            `;
            return;
        }

        // ================================
        // NOTA VÁLIDA
        // ================================
        soma += nota;
        total++;

        let classe = "barra-ruim";
        if (nota >= 75) classe = "barra-bom";
        else if (nota >= 40) classe = "barra-parcial";

        container.innerHTML += `
            <div class="barra-setor setor-item">

                <div class="barra-label">
                    <strong>${nome}</strong>
                </div>

                <div class="barra ${classe}" style="width:${nota}%;">
                    <span class="barra-nota">${nota}%</span>
                </div>

                <div class="setor-detalhes oculto">
                    ${criteriosHTML}

                    ${
                        obs.trim() !== ""
                        ? `<div class="obs-setor-detalhe"><strong>Obs:</strong> ${obs}</div>`
                        : ""
                    }
                </div>

            </div>
        `;
    });

    // ================================
    // ATIVAR CLIQUE PARA EXPANDIR
    // ================================
    document.querySelectorAll(".setor-item").forEach(item => {
        item.addEventListener("click", () => {
            item.querySelector(".setor-detalhes").classList.toggle("oculto");
        });
    });

    // ================================
    // GRÁFICO GERAL
    // ================================
    const geral = total > 0 ? soma / total : 0;
    montarGraficoGeral("grafico-geral", geral);
}



// ==========================================================
// FUNÇÃO COMUM PARA DESENHAR O GRÁFICO
// ==========================================================

function desenharGraficoGeral(ctx, nota) {

    let cor = "#e53935";
    if (nota >= 75) cor = "#43a047";
    else if (nota >= 40) cor = "#ffb300";

    const centerText = {
        id: "centerText",
        afterDraw(chart) {
            const { ctx, chartArea } = chart;
            if (!chartArea || chartArea.width === 0) return;

            const x = (chartArea.left + chartArea.right) / 2;
            const y = (chartArea.top + chartArea.bottom) / 2;

            ctx.save();
            ctx.fillStyle = "#333";
            ctx.font = "bold 20px Arial";
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            ctx.fillText(nota.toFixed(2) + "%", x, y);
            ctx.restore();
        }
    };

    new Chart(ctx, {
        type: "doughnut",
        plugins: [centerText],
        data: {
            datasets: [{
                data: [nota, 100 - nota],
                backgroundColor: [cor, "#e0e0e0"],
                borderWidth: 0
            }]
        },
        options: {
            cutout: "70%",
            responsive: false,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false }
            }
        }
    });
}



// ==========================================================
// GRÁFICO GERAL (REUTILIZADO NO MODAL E NO RESUMO)
// ==========================================================

function montarGraficoGeral(canvasId, nota) {

    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    const chartExistente = Chart.getChart(canvasId);
    if (chartExistente) {
        chartExistente.destroy();
    }

    let ctx;

    if (canvasId === "modal-grafico-geral") {
        ctx = canvas.getContext("2d");
    } else {
        const newCanvas = document.createElement("canvas");
        newCanvas.id = canvasId;
        newCanvas.width = canvas.width;
        newCanvas.height = canvas.height;
        canvas.parentNode.replaceChild(newCanvas, canvas);
        ctx = newCanvas.getContext("2d");
    }

    desenharGraficoGeral(ctx, nota);
}



// ==========================================================
// ABRIR MODAL DE DETALHES
// ==========================================================

function abrirModalDetalhes(id) {

    fetch("/ajax/carregar_detalhes_avaliacao.php?id=" + id)
        .then(r => r.json())
        .then(dados => {

            const av = dados.avaliacao;
            const setores = dados.setores || [];

            document.getElementById("modal-loja").textContent = av.loja;
            document.getElementById("modal-data").textContent = av.data_avaliacao;
            document.getElementById("modal-responsavel").textContent = av.responsavel_nome;

            const container = document.getElementById("modal-grafico-setores");
            container.innerHTML = "";

            setores.forEach(s => {

                const nota = Number(s.nota_setor);

                if (nota === -1) {
                    container.innerHTML += `
                        <div class="barra-setor">
                            <div class="barra-label">${s.setor}</div>
                            <div class="barra barra-na">N/A</div>
                        </div>
                    `;
                    return;
                }

                let classe = "barra-ruim";
                if (nota >= 75) classe = "barra-bom";
                else if (nota >= 40) classe = "barra-parcial";

                container.innerHTML += `
                    <div class="barra-setor">
                        <div class="barra-label">${s.setor}</div>
                        <div class="barra ${classe}" style="width:${nota}%;"></div>
                    </div>
                `;
            });

            mostrarModal();

            montarGraficoGeral("modal-grafico-geral", Number(av.nota_geral));
        });
}



// ==========================================================
// EVENTOS DO MODAL
// ==========================================================

const modal = document.getElementById("modal-detalhes");
const fechar = document.getElementById("fechar-modal");

function mostrarModal() {
    modal.classList.remove("oculto");
}

function fecharModal() {
    modal.classList.add("oculto");
}

fechar.onclick = fecharModal;

modal.onclick = (e) => {
    if (e.target === modal) fecharModal();
};

document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") fecharModal();
});



// ==========================================================
// DELEGAR CLIQUE NO BOTÃO DETALHES
// ==========================================================

document.addEventListener("click", e => {
    const btn = e.target.closest(".btn-detalhes");
    if (btn) {
        abrirModalDetalhes(btn.dataset.id);
    }
});
