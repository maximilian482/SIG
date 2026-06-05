document.addEventListener("click", async (e) => {

    function formatarData(data) {
        const d = new Date(data);
        return d.toLocaleDateString("pt-BR");
    }

    // ================================
    // BOTÃO EXCLUIR
    // ================================
    const btnExcluir = e.target.closest(".btn-excluir");
    if (btnExcluir) {

        const id = btnExcluir.dataset.id;

        if (!confirm("Tem certeza que deseja excluir esta auditoria?")) return;

        const resp = await fetch("/modulos/auditoria_pp_excluir.php?id=" + id, {
            method: "DELETE"
        });

        const resultado = await resp.json();

        if (resultado.status === "ok") {
            btnExcluir.closest("tr").nextElementSibling.remove(); // remove linha-detalhes
            btnExcluir.closest("tr").remove();                    // remove linha principal
        }

        return;
    }

    // ================================
    // BOTÃO DETALHES
    // ================================
    const btn = e.target.closest(".btn-detalhes");
    if (!btn) return;

    const id = btn.dataset.id;
    const linha = btn.closest("tr");
    const detalhes = linha.nextElementSibling; // já criamos no HTML

    if (!detalhes || !detalhes.classList.contains("linha-detalhes")) return;

    // toggle se já carregado
    if (detalhes.dataset.loaded === "1") {
        detalhes.classList.toggle("oculto");
        return;
    }

    detalhes.classList.remove("oculto");

    const wrapper = detalhes.querySelector(".detalhes-conteudo");
    wrapper.innerHTML = `
        <div class="detalhes-col">
            <h4>Carregando detalhes...</h4>
        </div>
    `;

    try {
        const resp = await fetch("/modulos/auditoria_pp_detalhes.php?id=" + id);
        const dados = await resp.json();

        const aud = dados.auditoria;
        const itens = dados.itens || [];

        wrapper.innerHTML = `
            <div class="detalhes-col">
                <h4>${aud.loja}</h4>
                <p><strong>Data:</strong> ${formatarData(aud.data_auditoria)}</p>
                <p><strong>Responsável:</strong> ${aud.responsavel_nome}</p>
                <p><strong>Avaliador:</strong> ${aud.avaliador}</p>
                <p><strong>Nota geral:</strong> ${parseFloat(aud.nota_geral).toFixed(2)}%</p>
                <canvas id="detalhe-grafico-geral-${id}" class="detalhe-grafico-geral" width="220" height="220"></canvas>
            </div>

            <div class="detalhes-col">
                <h4>Itens avaliados</h4>
                <div id="detalhe-itens-${id}"></div>
            </div>

            <div class="detalhes-col detalhes-assinatura" style="flex-basis:100%;">
                <h4>Assinatura do responsável</h4>
                ${
                    aud.assinatura
                    ? `<img src="${aud.assinatura}" alt="Assinatura" class="img-assinatura">`
                    : '<p>Sem assinatura registrada.</p>'
                }
            </div>
        `;

        const contItens = document.getElementById("detalhe-itens-" + id);

        itens.forEach(s => {

            // Converter 0 / 5 / 10 → 0 / 50 / 100
            let nota = 0;

            if (s.resposta == 10) nota = 100;
            else if (s.resposta == 5) nota = 50;
            else nota = 0;

            // Definir cor
            let classe = "barra-ruim";
            if (nota >= 75) classe = "barra-bom";
            else if (nota >= 40) classe = "barra-parcial";

            contItens.innerHTML += `
                <div class="barra-setor setor-item">

                    <div class="barra-label">
                        <strong>${s.pergunta}</strong>
                    </div>

                    <div class="barra ${classe}" style="width:${nota}%;">
                        <span class="barra-nota">${nota}%</span>
                    </div>

                    <div class="setor-detalhes oculto">
                        <div class="criterio-linha">
                            <strong>Resposta:</strong> ${
                                s.resposta == 10 ? "SIM" :
                                s.resposta == 5  ? "PARCIAL" :
                                "NÃO"
                            }
                        </div>

                        ${
                            s.observacao && s.observacao.trim() !== ""
                            ? `<div class="obs-setor-detalhe"><strong>Obs:</strong> ${s.observacao}</div>`
                            : ""
                        }
                    </div>

                </div>
            `;

        });

        detalhes.querySelectorAll(".setor-item").forEach(item => {
            item.addEventListener("click", () => {
                const d = item.querySelector(".setor-detalhes");
                d.classList.toggle("oculto");
            });
        });

        if (typeof montarGraficoGeral === "function") {

    let notaGeral = parseFloat(aud.nota_geral);

    // Converter 0 / 5 / 10 → 0 / 50 / 100
    if (notaGeral === 10) notaGeral = 100;
    else if (notaGeral === 5) notaGeral = 50;
    else notaGeral = 0;

    montarGraficoGeral("detalhe-grafico-geral-" + id, notaGeral);
}


        detalhes.dataset.loaded = "1";

    } catch (err) {
        wrapper.innerHTML = `<p>Erro ao carregar detalhes da auditoria.</p>`;
    }
});
