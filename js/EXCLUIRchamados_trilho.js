document.addEventListener("DOMContentLoaded", () => {

    // ============================
    // TROCA DE ABAS
    // ============================
    document.querySelectorAll(".aba").forEach(btn => {
        btn.addEventListener("click", () => {

            // Remove ativo de todas
            document.querySelectorAll(".aba").forEach(b => b.classList.remove("ativa"));
            document.querySelectorAll(".conteudo-aba").forEach(c => c.classList.remove("ativo"));

            // Ativa aba clicada
            btn.classList.add("ativa");
            document.getElementById(btn.dataset.aba).classList.add("ativo");

            // Mostrar filtros corretos
            const aba = btn.dataset.aba;

            document.getElementById("filtros-coletar").style.display   = (aba === "abertos")   ? "flex" : "none";
            document.getElementById("filtros-rota").style.display      = (aba === "rota")      ? "flex" : "none";
            document.getElementById("filtros-entregues").style.display = (aba === "entregues") ? "flex" : "none";
        });
    });

    // ============================
    // CONTADORES
    // ============================
    function atualizarContadores() {
        const totalAbertos   = document.querySelectorAll("#abertos .card-trilho").length;
        const totalRota      = document.querySelectorAll("#rota .card-trilho").length;
        const totalEntregues = document.querySelectorAll("#entregues .card-trilho").length;

        const abaAbertos   = document.querySelector('button[data-aba="abertos"]');
        const abaRota      = document.querySelector('button[data-aba="rota"]');
        const abaEntregues = document.querySelector('button[data-aba="entregues"]');

        if (abaAbertos)   abaAbertos.innerText   = `Abertos (${totalAbertos})`;
        if (abaRota)      abaRota.innerText      = `Em rota (${totalRota})`;
        if (abaEntregues) abaEntregues.innerText = `Entregues (${totalEntregues})`;
    }

    atualizarContadores();

    // ============================
    // MODAL DE DETALHES
    // ============================
    const modal = document.getElementById("modalDetalhes");
    const modalBody = document.getElementById("modal-body-detalhes");
    const fechar = document.querySelector(".modal-fechar");

    if (fechar) {
        fechar.addEventListener("click", () => modal.style.display = "none");
    }

    window.addEventListener("click", (e) => {
        if (e.target === modal) modal.style.display = "none";
    });

    document.addEventListener("click", (e) => {
        const btn = e.target.closest(".btn-detalhes");
        if (!btn) return;

        const id = btn.dataset.id;
        if (!id) return;

        modal.style.display = "flex";
        modalBody.innerHTML = "Carregando...";

        fetch("/modulos/chamados_trilho_detalhes.php?id=" + id)
            .then(r => r.text())
            .then(html => modalBody.innerHTML = html)
            .catch(() => modalBody.innerHTML = "<p style='color:red;'>Erro ao carregar detalhes.</p>");
    });

    // ============================
    // EXCLUIR PROTOCOLO
    // ============================
    document.addEventListener("click", (e) => {
        const btn = e.target.closest(".btn-excluir");
        if (!btn) return;

        const id = btn.dataset.id;
        if (!id) return;

        if (!confirm("Tem certeza que deseja excluir este protocolo?")) return;

        fetch("chamados_trilho_excluir.php", {
            method: "POST",
            body: new URLSearchParams({ id })
        })
        .then(r => r.json())
        .then(res => {
            if (res.sucesso) {
                mostrarMensagem(res.mensagem, "sucesso");
                setTimeout(() => location.reload(), 1200);
            } else {
                mostrarMensagem(res.mensagem, "erro");
            }
        })
        .catch(() => mostrarMensagem("Erro ao excluir protocolo.", "erro"));
    });

    // ============================
    // FATURAR — MODAL
    // ============================
    let idParaFaturar = null;

    document.addEventListener("click", (e) => {
        const btn = e.target.closest(".btn-faturar");
        if (!btn) return;

        idParaFaturar = btn.dataset.id;

        document.getElementById("notaTransferencia").value = "";
        document.getElementById("modalFaturar").style.display = "flex";
    });

    const fecharModalFaturar   = document.getElementById("fecharModalFaturar");
    const btnCancelarFaturar   = document.getElementById("btnCancelarFaturar");
    const btnConfirmarFaturar  = document.getElementById("btnConfirmarFaturar");
    const modalFaturar         = document.getElementById("modalFaturar");

    if (fecharModalFaturar) {
        fecharModalFaturar.onclick = () => {
            modalFaturar.style.display = "none";
        };
    }

    if (btnCancelarFaturar) {
        btnCancelarFaturar.onclick = () => {
            modalFaturar.style.display = "none";
        };
    }

    window.addEventListener("click", (e) => {
        if (e.target === modalFaturar) modalFaturar.style.display = "none";
    });

    if (btnConfirmarFaturar) {
        btnConfirmarFaturar.onclick = () => {

            const nota = document.getElementById("notaTransferencia").value.trim();

            if (!nota) {
                mostrarMensagem("Informe o número da nota de transferência.", "erro");
                modalFaturar.style.display = "none";
                return;
            }

            fetch("chamados_trilho_faturar.php", {
                method: "POST",
                body: new URLSearchParams({
                    id: idParaFaturar,
                    nota_transferencia: nota
                })
            })
            .then(r => r.json())
            .then(res => {
                if (res.sucesso) {
                    mostrarMensagem(res.mensagem, "sucesso");
                    setTimeout(() => location.reload(), 1200);
                } else {
                    mostrarMensagem(res.mensagem, "erro");
                }
            })
            .catch(() => mostrarMensagem("Erro ao faturar protocolo.", "erro"));

            modalFaturar.style.display = "none";
        };
    }

    // ============================
    // COLETAR PROTOCOLO (ADM)
    // ============================
    document.addEventListener("click", (e) => {
        const btn = e.target.closest(".btn-coletar");
        if (!btn) return;

        const id = btn.dataset.id;
        if (!id) return;

        fetch("chamados_trilho_coletar.php", {
            method: "POST",
            body: new URLSearchParams({ id })
        })
        .then(r => r.json())
        .then(res => {
            if (res.sucesso) {
                mostrarMensagem(res.mensagem, "sucesso");
                setTimeout(() => location.reload(), 1200);
            } else {
                mostrarMensagem(res.mensagem, "erro");
            }
        })
        .catch(() => mostrarMensagem("Erro ao coletar protocolo.", "erro"));
    });

    // ============================
    // FINALIZAR ENTREGA (ADM)
    // ============================
    document.addEventListener("click", (e) => {
        const btn = e.target.closest(".btn-entregar");
        if (!btn) return;

        const id = btn.dataset.id;
        if (!id) return;

        fetch("chamados_trilho_entregar.php", {
            method: "POST",
            body: new URLSearchParams({ id })
        })
        .then(r => r.json())
        .then(res => {
            if (res.sucesso) {
                mostrarMensagem(res.mensagem, "sucesso");
                setTimeout(() => location.reload(), 1200);
            } else {
                mostrarMensagem(res.mensagem, "erro");
            }
        })
        .catch(() => mostrarMensagem("Erro ao finalizar entrega.", "erro"));
    });

    // ============================================================
    // FILTROS POR ABA (COM AJAX, AGORA PARA O ADM)
    // ============================================================

    const filtroLib      = document.getElementById("filtro-lib");       // ABERTOS (Liberação / Destino)
    const filtroSolic    = document.getElementById("filtro-solic");     // ROTA (Origem)
    const filtroEntregue = document.getElementById("filtro-entregue");  // ENTREGUES (Destino)

    const btnLimparColetar   = document.getElementById("btn-limpar-coletar");
    const btnLimparRota      = document.getElementById("btn-limpar-rota");
    const btnLimparEntregues = document.getElementById("btn-limpar-entregues");

    const boxAbertos   = document.querySelector("#abertos");
    const boxRota      = document.querySelector("#rota");
    const boxEntregues = document.querySelector("#entregues");

    if (filtroLib && filtroSolic && filtroEntregue) {

        // Carregar listas (origens/destinos)
        fetch("/ajax/trilho_filtros_listas.php")
            .then(r => r.json())
            .then(dados => {

                // Destinos → usados em Abertos (Liberação) e Entregues
                dados.destinos.forEach(d => {
                    filtroLib.innerHTML      += `<option value="${d.id}">${d.nome}</option>`;
                    filtroEntregue.innerHTML += `<option value="${d.id}">${d.nome}</option>`;
                });

                // Origens → usados em Rota
                dados.origens.forEach(o => {
                    filtroSolic.innerHTML += `<option value="${o.id}">${o.nome}</option>`;
                });

                atualizarAbertos();
                atualizarRota();
                atualizarEntregues();
            });

        // AJAX ABERTOS (usa trilho_abertos.php)
        function atualizarAbertos() {
            const lib = filtroLib.value;

            boxAbertos.innerHTML = `<h3>📦 Abertos</h3><p class="loading">Carregando...</p>`;

            fetch(`/ajax/trilho_abertos.php?lib=${encodeURIComponent(lib)}`)
                .then(r => r.text())
                .then(html => {
                    boxAbertos.innerHTML = `<h3>📦 Abertos</h3>` + html;
                    atualizarContadores();
                });
        }

        // AJAX ROTA (usa trilho_rota.php)
        function atualizarRota() {
            const solic = filtroSolic.value;

            boxRota.innerHTML = `<h3>🛵 Em Rota</h3><p class="loading">Carregando...</p>`;

            fetch(`/ajax/trilho_rota.php?solic=${encodeURIComponent(solic)}`)
                .then(r => r.text())
                .then(html => {
                    boxRota.innerHTML = `<h3>🛵 Em Rota</h3>` + html;
                    atualizarContadores();
                });
        }

        // AJAX ENTREGUES (usa trilho_entregues.php)
        function atualizarEntregues() {
            const loja = filtroEntregue.value;

            boxEntregues.innerHTML = `<h3>📦 Entregues</h3><p class="loading">Carregando...</p>`;

            fetch(`/ajax/trilho_entregues.php?loja=${encodeURIComponent(loja)}`)
                .then(r => r.text())
                .then(html => {
                    boxEntregues.innerHTML = `<h3>📦 Entregues</h3>` + html;
                    atualizarContadores();
                });
        }

        // Eventos dos filtros
        filtroLib.addEventListener("change", atualizarAbertos);
        filtroSolic.addEventListener("change", atualizarRota);
        filtroEntregue.addEventListener("change", atualizarEntregues);

        // Botões limpar
        if (btnLimparColetar) {
            btnLimparColetar.addEventListener("click", () => {
                filtroLib.value = "";
                atualizarAbertos();
            });
        }

        if (btnLimparRota) {
            btnLimparRota.addEventListener("click", () => {
                filtroSolic.value = "";
                atualizarRota();
            });
        }

        if (btnLimparEntregues) {
            btnLimparEntregues.addEventListener("click", () => {
                filtroEntregue.value = "";
                atualizarEntregues();
            });
        }
    }

});
