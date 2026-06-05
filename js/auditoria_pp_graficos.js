console.log("JS Auditoria PP — gráficos carregado!");

// ==========================================================
// GERAR RESUMO — Auditoria PP (versão final)
// ==========================================================
function gerarResumo() {

    const container = document.getElementById("grafico-itens");
    if (!container) return;

    container.innerHTML = "";

    let soma = 0;
    let total = 0;

    document.querySelectorAll(".carrossel-slide[data-item-id]").forEach(slide => {

        const nome = slide.querySelector("h3").innerText;

        let notaOriginal = parseInt(slide.querySelector(".input-nota").value || -1);
        const obs  = slide.querySelector(".obs-item")?.value || "";

        // ================================
        // CASO N/A
        // ================================
        if (notaOriginal === -1 || isNaN(notaOriginal)) {
            container.innerHTML += `
                <div class="barra-setor setor-item">
                    <div class="barra-label"><strong>${nome}</strong></div>
                    <div class="barra barra-na">N/A</div>
                </div>
            `;
            return;
        }

        // ================================
        // CONVERTER 0 / 5 / 10 → 0 / 50 / 100
        // ================================
        let nota = 0;

        if (notaOriginal === 10) nota = 100;
        else if (notaOriginal === 5) nota = 50;
        else nota = 0;

        // ================================
        // SOMAR PARA O GRÁFICO GERAL
        // ================================
        soma += nota;
        total++;

        // ================================
        // DEFINIR COR
        // ================================
        let classe = "barra-ruim";
        if (nota >= 75) classe = "barra-bom";
        else if (nota >= 40) classe = "barra-parcial";

        // largura visual mínima
        let largura = nota;
        if (nota === 0) largura = 5;

        container.innerHTML += `
            <div class="barra-setor setor-item">

                <div class="barra-label">
                    <strong>${nome}</strong>
                </div>

                <div class="barra ${classe}" style="width:${largura}%;">
                    <span class="barra-nota">${nota}%</span>
                </div>

                <div class="setor-detalhes oculto">
                    ${
                        obs.trim() !== ""
                        ? `<div class="obs-setor-detalhe"><strong>Obs:</strong> ${obs}</div>`
                        : "<em>Sem observações.</em>"
                    }
                </div>

            </div>
        `;

    });

    // ================================
    // EXPANDIR DETALHES
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

    console.log("NOTA GERAL CALCULADA:", geral);

    montarGraficoGeral("grafico-geral", geral);
}



// ==========================================================
// DESENHAR GRÁFICO GERAL (donut)
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
            ctx.font = "bold 22px Arial";
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
// MONTAR GRÁFICO GERAL (SEM RECRIAR CANVAS)
// ==========================================================
function montarGraficoGeral(canvasId, nota) {

    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    // destruir gráfico anterior se existir
    const chartExistente = Chart.getChart(canvasId);
    if (chartExistente) chartExistente.destroy();

    const ctx = canvas.getContext("2d");

    desenharGraficoGeral(ctx, nota);
}
