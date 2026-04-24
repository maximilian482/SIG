// ==========================================================
// GERAR RESUMO (GRÁFICOS)
// ==========================================================

function gerarResumo() {

    const container = document.getElementById("grafico-setores");
    container.innerHTML = "";

    let soma = 0;
    let total = 0;

    document.querySelectorAll(".carrossel-slide[data-setor-id]").forEach(slide => {

        const nome = slide.querySelector(".titulo-setor").innerText;
        const nota = parseFloat(slide.querySelector(".nota-setor-auto").value);

        soma += nota;
        total++;

        let classe = "barra-ruim";
        if (nota >= 75) classe = "barra-bom";
        else if (nota >= 40) classe = "barra-parcial";

        container.innerHTML += `
            <div class="barra-setor">
                <div class="barra-label">${nome}</div>
                <div class="barra ${classe}" style="width:${nota}%;"></div>
            </div>
        `;
    });

    const geral = soma / total;

    montarGraficoGeral("grafico-geral", geral);
}



// ==========================================================
// GRÁFICO GERAL (REUTILIZADO NO MODAL E NO RESUMO)
// ==========================================================

function montarGraficoGeral(canvasId, nota) {

    // 🔥 RESETAR O CANVAS SEM PERDER O ID
    const oldCanvas = document.getElementById(canvasId);
    const newCanvas = document.createElement("canvas");
    newCanvas.id = canvasId; // mantém o ID
    newCanvas.width = oldCanvas.width;
    newCanvas.height = oldCanvas.height;
    oldCanvas.parentNode.replaceChild(newCanvas, oldCanvas);

    const ctx = newCanvas.getContext("2d");

    let cor = "#e53935";
    if (nota >= 75) cor = "#43a047";
    else if (nota >= 40) cor = "#ffb300";

    // Plugin para texto central
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
            cutout: "70%", // igual ao resumo
            responsive: false,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false }
            }
        }
    });
}





// ==========================================================
// ABRIR MODAL DE DETALHES
// ==========================================================

function abrirModalDetalhes(id) {

    fetch("/ajax/carregar_detalhes_avaliacao.php?id=" + id)
        .then(r => r.json())
        .then(dados => {

            const av = dados.avaliacao;
            const setores = dados.setores;

            document.getElementById("modal-loja").textContent = av.loja;
            document.getElementById("modal-data").textContent = av.data_avaliacao;
            document.getElementById("modal-responsavel").textContent = av.responsavel_nome;

            const container = document.getElementById("modal-grafico-setores");
            container.innerHTML = "";

            setores.forEach(s => {

                let classe = "barra-ruim";
                if (s.nota_setor >= 75) classe = "barra-bom";
                else if (s.nota_setor >= 40) classe = "barra-parcial";

                container.innerHTML += `
                    <div class="barra-setor">
                        <div class="barra-label">${s.setor}</div>
                        <div class="barra ${classe}" style="width:${s.nota_setor}%;"></div>
                    </div>
                `;
            });

            mostrarModal();

            // 🔥 AGORA FUNCIONA: gráfico só depois do modal abrir
            setTimeout(() => {
                montarGraficoGeral("modal-grafico-geral", av.nota_geral);
            }, 80);
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
