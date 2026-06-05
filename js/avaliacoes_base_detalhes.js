document.addEventListener("click", async (e) => {

    // ================================
    // FORMATAR DATA
    // ================================
    function formatarData(data) {
        const d = new Date(data);
        return d.toLocaleDateString("pt-BR");
    }

    // ================================
    // BOTÃO EXCLUIR (MODELO ANTIGO)
    // ================================
    const btnExcluir = e.target.closest(".btn-excluir");
    if (btnExcluir) {

        const id = btnExcluir.dataset.id;

        if (!confirm("Tem certeza que deseja excluir esta avaliação?")) {
            return;
        }

        const resp = await fetch("/ajax/avaliacao_excluir.php?id=" + id, {
            method: "DELETE"
        });

        const resultado = await resp.json();

        if (resultado.sucesso) {
            btnExcluir.closest("tr").remove();
        } else {
            alert("Erro ao excluir: " + resultado.mensagem);
        }

        return;
    }

    // ================================
    // BOTÃO DETALHES (MODELO ANTIGO)
    // ================================
    const btn = e.target.closest(".btn-detalhes");
    if (!btn) return;

    const id = btn.dataset.id;
    const linha = btn.closest("tr");
    const tbody = linha.parentNode;

    // Se já existe a linha de detalhes → só alterna
    let detalhes = linha.nextElementSibling;
    if (detalhes && detalhes.classList.contains("linha-detalhes")) {
        detalhes.classList.toggle("oculto");
        return;
    }

    // Criar linha de detalhes
    detalhes = document.createElement("tr");
    detalhes.className = "linha-detalhes";
    detalhes.innerHTML = `
        <td colspan="4">
            <div class="detalhes-conteudo">
                <h4>Carregando detalhes...</h4>
            </div>
        </td>
    `;
    tbody.insertBefore(detalhes, linha.nextSibling);

    // ================================
    // BUSCAR DETALHES
    // ================================
    try {
        const resp = await fetch("/ajax/carregar_detalhes_avaliacao.php?id=" + id);
        const dados = await resp.json();

        const av = dados.avaliacao;
        const setores = dados.setores || [];

        const wrapper = detalhes.querySelector(".detalhes-conteudo");

        wrapper.innerHTML = `
            <div class="detalhes-col">
                <h4>${av.loja}</h4>
                <p><strong>Data:</strong> ${formatarData(av.data_avaliacao)}</p>
                <p><strong>Responsável:</strong> ${av.responsavel_nome}</p>
                <p><strong>Avaliador:</strong> ${av.avaliador_nome}</p>
                <p><strong>Nota geral:</strong> ${parseFloat(av.nota_geral).toFixed(2)}%</p>
                <canvas id="detalhe-grafico-geral-${id}" width="220" height="220"></canvas>
            </div>

            <div class="detalhes-col">
                <h4>Setores avaliados</h4>
                <div id="detalhe-setores-${id}"></div>
            </div>

            <div class="detalhes-col detalhes-assinatura">
                <h4>Assinatura do responsável</h4>
                ${
                    av.assinatura
                    ? `<img src="${av.assinatura}" class="img-assinatura">`
                    : '<p>Sem assinatura registrada.</p>'
                }
            </div>
        `;

        // ================================
        // SETORES
        // ================================
        const contSetores = document.getElementById("detalhe-setores-" + id);

        setores.forEach(s => {

            let classe = "barra-ruim";
            if (s.nota_setor >= 75) classe = "barra-bom";
            else if (s.nota_setor >= 40) classe = "barra-parcial";

            let criteriosHTML = "";
            if (s.criterios && s.criterios.length > 0) {
                criteriosHTML = s.criterios.map(c => {

                    const valor = parseInt(c.valor);

                    let texto = "N/A";
                    if (valor === 100) texto = "SIM";
                    else if (valor === 50) texto = "PARCIAL";
                    else if (valor === 0) texto = "NÃO";

                    return `
                        <div class="criterio-linha">
                            <strong>${c.criterio}:</strong> ${texto}
                        </div>
                    `;
                }).join("");
            }

            contSetores.innerHTML += `
                <div class="barra-setor setor-item">

                    <div class="barra-label">
                        <strong>${s.setor}</strong>
                    </div>

                    <div class="barra ${classe}" style="width:${s.nota_setor}%;">
                        <span class="barra-nota">${s.nota_setor}%</span>
                    </div>

                    <div class="setor-detalhes oculto">
                        ${criteriosHTML}
                        ${
                            s.observacao
                            ? `<div class="obs-setor-detalhe"><strong>Obs:</strong> ${s.observacao}</div>`
                            : ""
                        }
                    </div>

                </div>
            `;
        });

        // Clique para expandir critérios
        document.querySelectorAll(".setor-item").forEach(item => {
            item.addEventListener("click", () => {
                item.querySelector(".setor-detalhes").classList.toggle("oculto");
            });
        });

        // Gráfico geral
        if (typeof montarGraficoGeral === "function") {
            montarGraficoGeral("detalhe-grafico-geral-" + id, parseFloat(av.nota_geral));
        }

    } catch (err) {
        console.error("Erro ao carregar detalhes:", err);
        detalhes.querySelector(".detalhes-conteudo").innerHTML =
            "<p>Erro ao carregar detalhes.</p>";
    }
});
