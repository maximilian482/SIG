document.addEventListener("DOMContentLoaded", () => {

    // ============================
    // TROCA DE ABAS
    // ============================
    document.querySelectorAll(".aba").forEach(btn => {
        btn.addEventListener("click", () => {
            document.querySelectorAll(".aba").forEach(b => b.classList.remove("ativa"));
            document.querySelectorAll(".conteudo-aba").forEach(c => c.classList.remove("ativo"));

            btn.classList.add("ativa");
            document.getElementById(btn.dataset.aba).classList.add("ativo");

            const aba = btn.dataset.aba;
            document.getElementById("filtros-coletar").style.display = (aba === "coletar") ? "flex" : "none";
            document.getElementById("filtros-rota").style.display = (aba === "rota") ? "flex" : "none";
            document.getElementById("filtros-entregues").style.display = (aba === "entregues") ? "flex" : "none";
        });
    });

    // ============================
    // CONTADORES
    // ============================
    function atualizarContadores() {
        const totalColetar = document.querySelectorAll("#coletar .card-trilho").length;
        const totalRota = document.querySelectorAll("#rota .card-trilho").length;
        const totalEntregues = document.querySelectorAll("#entregues .card-trilho").length;

        document.querySelector('button[data-aba="coletar"]').innerText = `Coletar (${totalColetar})`;
        document.querySelector('button[data-aba="rota"]').innerText = `Em rota (${totalRota})`;
        document.querySelector('button[data-aba="entregues"]').innerText = `Entregues (${totalEntregues})`;
    }

    atualizarContadores();

   // ============================
// MODAL DE DETALHES
// ============================
const modal = document.getElementById("modalDetalhes");
const modalBody = document.getElementById("modal-body-detalhes");
const fechar = document.querySelector(".modal-fechar");

fechar.addEventListener("click", () => modal.style.display = "none");
window.addEventListener("click", (e) => {
    if (e.target === modal) modal.style.display = "none";
});

// Abrir modal
document.addEventListener("click", async (e) => {

    // TIPOS SIMPLES
    const btnSimples = e.target.closest(".btn-detalhes-simples");
    if (btnSimples) {

        const id = btnSimples.dataset.id;
        modal.style.display = "flex";
        modalBody.innerHTML = "Carregando...";

        const res = await fetch(`/ajax/trilho_detalhes_simples.php?id=${id}`);
        const dados = await res.json();

        if (dados.erro) {
            modalBody.innerHTML = `<p style="color:red;">${dados.erro}</p>`;
            return;
        }

        // Monta o HTML igual ADM
        modalBody.innerHTML = `
            <h3>${dados.tipo}</h3>

            <p><strong id="lbl-origem"></strong> ${dados.origem}</p>
            <p><strong id="lbl-destino"></strong> ${dados.destino}</p>
            <p><strong id="lbl-responsavel"></strong> ${dados.responsavel}</p>

            <p><strong>Descrição:</strong><br>${dados.descricao}</p>
            <p><strong>Observações:</strong><br>${dados.observacoes}</p>
        `;

        // Ajusta rótulos conforme ação
        if (dados.acao === "enviar") {
            document.getElementById("lbl-origem").innerText = "Origem:";
            document.getElementById("lbl-destino").innerText = "Destino:";
            document.getElementById("lbl-responsavel").innerText = "Aos cuidados de:";
        } else {
            document.getElementById("lbl-origem").innerText = "Enviado por:";
            document.getElementById("lbl-destino").innerText = "Recebido por:";
            document.getElementById("lbl-responsavel").innerText = "Responsável:";
        }

        return;
    }

    // TIPOS NÃO SIMPLES
    const btnNormal = e.target.closest(".btn-detalhes");
    if (btnNormal) {

        const id = btnNormal.dataset.id;
        modal.style.display = "flex";
        modalBody.innerHTML = "Carregando...";

        fetch(`/modulos/chamados_trilho_detalhes.php?id=${id}`)
            .then(r => r.text())
            .then(html => modalBody.innerHTML = html)
            .catch(() => modalBody.innerHTML = "<p style='color:red;'>Erro ao carregar detalhes.</p>");

        return;
    }
});



    // ============================
    // COLETAR
    // ============================
    document.addEventListener("click", (e) => {
        if (e.target.classList.contains("btn-coletar")) {

            const id = e.target.dataset.id;
            if (!id) return;

            if (!confirm("Confirmar coleta?")) return;

            fetch("trilho_motoboy_coletar.php?id=" + id)
                .then(r => r.json())
                .then(res => {
                    if (res.sucesso) {
                        mostrarMensagem("Coleta registrada com sucesso!", "sucesso");
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        mostrarMensagem(res.mensagem || "Erro ao coletar.", "erro");
                    }
                })
                .catch(() => mostrarMensagem("Erro de comunicação com o servidor.", "erro"));
        }
    });

    // ============================
    // ENTREGAR
    // ============================
    document.addEventListener("click", (e) => {
        if (e.target.classList.contains("btn-entregar")) {

            const id = e.target.dataset.id;
            if (!id) return;

            mostrarMensagem("Redirecionando para assinatura...", "info");

            setTimeout(() => {
                window.location.href = "trilho_assinar.php?id=" + id;
            }, 600);
        }
    });

    // ============================================================
    // FILTROS POR ABA
    // ============================================================

    const filtroLib = document.getElementById("filtro-lib");
    const filtroSolic = document.getElementById("filtro-solic");
    const filtroEntregue = document.getElementById("filtro-entregue");

    const btnLimparColetar = document.getElementById("btn-limpar-coletar");
    const btnLimparRota = document.getElementById("btn-limpar-rota");
    const btnLimparEntregues = document.getElementById("btn-limpar-entregues");

    const boxColetar = document.querySelector("#coletar");
    const boxRota = document.querySelector("#rota");
    const boxEntregues = document.querySelector("#entregues");

    // Carregar listas
    fetch("/ajax/trilho_filtros_listas.php")
    .then(r => r.json())
    .then(dados => {
        console.log(dados); // coloca isso pra ver no console se veio mesmo

        dados.destinos.forEach(d => {
            filtroLib.innerHTML      += `<option value="${d.id}">${d.nome}</option>`;
            filtroEntregue.innerHTML += `<option value="${d.id}">${d.nome}</option>`;
        });

        dados.origens.forEach(o => {
            filtroSolic.innerHTML += `<option value="${o.id}">${o.nome}</option>`;
        });
    })
    .catch(err => console.error("Erro ao carregar listas do trilho:", err));


    // AJAX COLETAR
    function atualizarColetar() {
        const lib = filtroLib.value;

        fetch(`/ajax/trilho_filtros_coletar.php?lib=${lib}`)
            .then(r => r.text())
            .then(html => {
                boxColetar.innerHTML = `<h3>📦 Transferências para Coletar</h3>` + html;
                atualizarContadores();
            });
    }

    // AJAX ROTA
    function atualizarRota() {
        const solic = filtroSolic.value;

        fetch(`/ajax/trilho_filtros_rota.php?solic=${solic}`)
            .then(r => r.text())
            .then(html => {
                boxRota.innerHTML = `<h3>🛵 Transferências em Rota</h3>` + html;
                atualizarContadores();
            });
    }

    // AJAX ENTREGUES
    function atualizarEntregues() {
        const loja = filtroEntregue.value;

        fetch(`/ajax/trilho_filtros_entregues.php?loja=${loja}`)
            .then(r => r.text())
            .then(html => {
                boxEntregues.innerHTML = `<h3>📄 Entregues (Hoje)</h3>` + html;
                atualizarContadores();
            });
    }

    // Eventos dos filtros
    filtroLib.addEventListener("change", atualizarColetar);
    filtroSolic.addEventListener("change", atualizarRota);
    filtroEntregue.addEventListener("change", atualizarEntregues);

    // Botões limpar
    btnLimparColetar.addEventListener("click", () => {
        filtroLib.value = "";
        atualizarColetar();
    });

    btnLimparRota.addEventListener("click", () => {
        filtroSolic.value = "";
        atualizarRota();
    });

    btnLimparEntregues.addEventListener("click", () => {
        filtroEntregue.value = "";
        atualizarEntregues();
    });

});



