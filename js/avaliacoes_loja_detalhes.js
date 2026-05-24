document.addEventListener("click", async (e) => {


    // FORMATAR DATA
    
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

            if (!confirm("Tem certeza que deseja excluir esta avaliação?")) {
                return;
            }

            const resp = await fetch("/ajax/avaliacao_excluir.php?id=" + id, {
                method: "DELETE"
            });

            const resultado = await resp.json();

            if (resultado.sucesso) {

                if (typeof mostrarMensagem === "function") {
                    mostrarMensagem("Avaliação excluída com sucesso!", "sucesso");
                }

                btnExcluir.closest("tr").remove();

            } else {

                if (typeof mostrarMensagem === "function") {
                    mostrarMensagem("Erro ao excluir: " + resultado.mensagem, "erro");
                }
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
    const tbody = linha.parentNode;

    let detalhes = linha.nextElementSibling;
    if (detalhes && detalhes.classList.contains("linha-detalhes")) {
        detalhes.classList.toggle("oculto");
        return;
    }

    detalhes = document.createElement("tr");
    detalhes.className = "linha-detalhes";
    detalhes.innerHTML = `
        <td colspan="4">
            <div class="detalhes-conteudo">
                <div class="detalhes-col">
                    <h4>Carregando detalhes...</h4>
                </div>
            </div>
        </td>
    `;
    tbody.insertBefore(detalhes, linha.nextSibling);

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
                <canvas id="detalhe-grafico-geral-${id}" class="detalhe-grafico-geral" width="220" height="220"></canvas>
            </div>

            <div class="detalhes-col">
                <h4>Setores avaliados</h4>
                <div id="detalhe-setores-${id}"></div>
            </div>

            <div class="detalhes-col detalhes-assinatura">
                <h4>Assinatura do responsável</h4>
                ${
                    av.assinatura
                    ? `<img src="${av.assinatura}" alt="Assinatura" class="img-assinatura">`
                    : '<p>Sem assinatura registrada.</p>'
                }
            </div>
        `;

        const contSetores = document.getElementById("detalhe-setores-" + id);

        setores.forEach(s => {

            let classe = "barra-ruim";
            if (s.nota_setor >= 75) classe = "barra-bom";
            else if (s.nota_setor >= 40) classe = "barra-parcial";

            // ================================
            // CRITÉRIOS — CORREÇÃO AQUI
            // ================================
            let criteriosHTML = "";
            if (s.criterios && s.criterios.length > 0) {
                criteriosHTML = s.criterios.map(c => {

                    const valor = parseInt(c.valor); // <-- ESSENCIAL

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
                            s.observacao && s.observacao.trim() !== ""
                            ? `<div class="obs-setor-detalhe"><strong>Obs:</strong> ${s.observacao}</div>`
                            : ""
                        }
                    </div>

                </div>
            `;
        });

        // Clique para expandir detalhes
        document.querySelectorAll(".setor-item").forEach(item => {
            item.addEventListener("click", () => {
                const detalhes = item.querySelector(".setor-detalhes");
                detalhes.classList.toggle("oculto");
            });
        });

        if (typeof montarGraficoGeral === "function") {
            montarGraficoGeral("detalhe-grafico-geral-" + id, parseFloat(av.nota_geral));
        }

    } catch (err) {
        console.error("Erro ao carregar detalhes:", err);
        const wrapper = detalhes.querySelector(".detalhes-conteudo");
        wrapper.innerHTML = `<p>Erro ao carregar detalhes da avaliação.</p>`;
    }
});

// ================================
// BOTÃO CONFIGURAR (VERSÃO SIMPLES E FUNCIONAL)
// ================================
document.addEventListener("DOMContentLoaded", () => {
    const selectLoja = document.getElementById("loja_id");
    const btnConfig = document.getElementById("btn-configurar");

    if (!selectLoja || !btnConfig) return;

    btnConfig.addEventListener("click", (e) => {
        const loja = selectLoja.value;

       

        btnConfig.href = "avaliacoes_setores.php?loja=" + loja;
    });
});
