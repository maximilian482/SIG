let pagina = 1;

document.addEventListener("DOMContentLoaded", () => {
    // NÃO CHAMA MAIS carregarLojasFiltro, pois as lojas vêm do PHP
    carregarHistorico();

    const btnFiltrar   = document.getElementById("btnFiltrar");
    const btnAnterior  = document.getElementById("btnAnterior");
    const btnProximo   = document.getElementById("btnProximo");

    if (btnFiltrar) {
        btnFiltrar.addEventListener("click", () => {
            pagina = 1;
            carregarHistorico();
        });
    }

    if (btnAnterior) {
        btnAnterior.addEventListener("click", () => {
            if (pagina > 1) {
                pagina--;
                carregarHistorico();
            }
        });
    }

    if (btnProximo) {
        btnProximo.addEventListener("click", () => {
            pagina++;
            carregarHistorico();
        });
    }
});

// Carrega histórico com filtros e paginação
function carregarHistorico() {

    const selLoja   = document.getElementById("filtro_loja");
    const inpIni    = document.getElementById("filtro_data_ini");
    const inpFim    = document.getElementById("filtro_data_fim");

    const loja     = selLoja ? selLoja.value : "";
    const data_ini = inpIni ? inpIni.value : "";
    const data_fim = inpFim ? inpFim.value : "";

    const url = `/ajax/auditoria_pp_historico_lista.php?pagina=${pagina}&loja=${encodeURIComponent(loja)}&data_ini=${encodeURIComponent(data_ini)}&data_fim=${encodeURIComponent(data_fim)}`;

    fetch(url)
        .then(res => res.json())
        .then(lista => {

            const spanPagina = document.getElementById("paginaAtual");
            if (spanPagina) {
                spanPagina.innerText = pagina;
            }

            const tbody = document.getElementById("lista-historico");
            if (!tbody) return;

            tbody.innerHTML = "";

            lista.forEach(a => {

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${a.loja}</td>
                    <td class="${a.classeNota}">${a.nota}%</td>
                    <td>${a.data}</td>
                    <td class="col-acoes">
                        <button class="btn-icone btn-detalhes" data-id="${a.id}">🔍</button>
                        <button class="btn-icone btn-excluir" data-id="${a.id}">🗑️</button>
                    </td>
                `;

                const trDetalhes = document.createElement("tr");
                trDetalhes.className = "linha-detalhes oculto";
                trDetalhes.innerHTML = `
                    <td colspan="4">
                        <div class="detalhes-conteudo"></div>
                    </td>
                `;

                tbody.appendChild(tr);
                tbody.appendChild(trDetalhes);
            });
        })
        .catch(err => {
            console.error("Erro ao carregar histórico:", err);
        });
}

// ===============================
// DETALHES E EXCLUSÃO
// ===============================
document.addEventListener("click", function(e) {

    // ABRIR DETALHES
    if (e.target.classList.contains("btn-detalhes")) {

        const id = e.target.dataset.id;
        if (!id) return;

        const linhaPrincipal = e.target.closest("tr");
        if (!linhaPrincipal) return;

        const linhaDetalhes = linhaPrincipal.nextElementSibling;
        if (!linhaDetalhes || !linhaDetalhes.classList.contains("linha-detalhes")) return;

        const conteudo = linhaDetalhes.querySelector(".detalhes-conteudo");
        if (!conteudo) return;

        // Se já está aberto, fecha
        if (!linhaDetalhes.classList.contains("oculto")) {
            linhaDetalhes.classList.add("oculto");
            conteudo.innerHTML = "";
            return;
        }

        fetch(`/ajax/auditoria_pp_historico_detalhes.php?id=${id}`)
            .then(res => res.text())
            .then(html => {
                conteudo.innerHTML = html;
                linhaDetalhes.classList.remove("oculto");
            })
            .catch(err => {
                console.error("Erro ao carregar detalhes:", err);
            });
    }

    /// EXCLUIR
if (e.target.classList.contains("btn-excluir")) {

    const id = e.target.dataset.id;
    if (!id) return;

    if (!confirm("Tem certeza que deseja excluir esta auditoria?")) return;

    fetch(`/ajax/auditoria_pp_excluir.php?id=${id}`)
        .then(res => res.text())
        .then(ret => {

            const linha = e.target.closest("tr");
            if (!linha) return;

            const linhaDetalhes = linha.nextElementSibling;
            if (linhaDetalhes && linhaDetalhes.classList.contains("linha-detalhes")) {
                linhaDetalhes.remove();
            }

            linha.remove();

            // 🔥 Mensagem premium
            mostrarMensagem("Auditoria excluída com sucesso!", "sucesso");
        })
        .catch(err => {
            console.error("Erro ao excluir auditoria:", err);

            // 🔥 Mensagem premium de erro
            mostrarMensagem("Erro ao excluir auditoria!", "erro");
        });
}


});
