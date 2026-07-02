console.log("auditoria_checklist_grafico.js carregado");

// ==========================================================
// GRÁFICO GERAL — MODELO A (igual Avaliação de Loja)
// ==========================================================
let graficoGeral = null;

function montarGraficoGeral(respostas) {

    const total = respostas.length;
    const soma = respostas.reduce((acc, r) => acc + parseInt(r.valor), 0);
    const media = total > 0 ? (soma / total) : 0;

    // Atualiza texto central
    const texto = document.getElementById("grafico-geral-texto");
    if (texto) texto.textContent = media.toFixed(0) + "%";

    const canvas = document.getElementById("grafico-geral");
    if (!canvas) {
        console.error("Canvas grafico-geral não encontrado.");
        return;
    }

    const ctx = canvas.getContext("2d");

    // Destroi gráfico anterior para evitar erro de instância duplicada
    if (graficoGeral) {
        graficoGeral.destroy();
    }

    graficoGeral = new Chart(ctx, {
        type: "doughnut",
        data: {
            labels: ["Nota"],
            datasets: [{
                data: [media, 100 - media],
                backgroundColor: ["#5cb85c", "#e0e0e0"],
                borderWidth: 0
            }]
        },
        options: {
            cutout: "70%",
            plugins: {
                legend: { display: false }
            }
        }
    });
}
