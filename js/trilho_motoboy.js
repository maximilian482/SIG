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

            // Mostrar filtros corretos
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

    fechar.addEventListener("click", () => {
        modal.style.display = "none";
    });

    window.addEventListener("click", (e) => {
        if (e.target === modal) {
            modal.style.display = "none";
        }
    });

    document.addEventListener("click", (e) => {
        if (e.target.classList.contains("btn-detalhes")) {

            const id = e.target.dataset.id;
            if (!id) return;

            modal.style.display = "flex";
            modalBody.innerHTML = "Carregando...";

            fetch("chamados_trilho_detalhes.php?id=" + id)
                .then(r => r.text())
                .then(html => {
                    modalBody.innerHTML = html;
                })
                .catch(() => {
                    modalBody.innerHTML = "<p style='color:red;'>Erro ao carregar detalhes.</p>";
                });
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
    // NOVO SISTEMA DE FILTROS POR ABA
    // ============================================================

    const filtroLib = document.getElementById("filtro-lib");           // Coletar
    const filtroSolic = document.getElementById("filtro-solic");       // Em rota
    const filtroEntregue = document.getElementById("filtro-entregue"); // Entregues

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

            dados.destinos.forEach(d => {
                filtroLib.innerHTML += `<option value="${d.id}">${d.nome}</option>`;
                filtroEntregue.innerHTML += `<option value="${d.id}">${d.nome}</option>`;
            });

            dados.origens.forEach(o => {
                filtroSolic.innerHTML += `<option value="${o.id}">${o.nome}</option>`;
            });
        });

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
