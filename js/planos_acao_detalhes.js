document.addEventListener("DOMContentLoaded", () => {

    /* ============================================================
       1) ABAS
    ============================================================ */
    const tabs = document.querySelectorAll(".tab");
    const rows = document.querySelectorAll("#tarefas-body tr");

    function ativarAba(filtro) {
        tabs.forEach(tab => {
            tab.setAttribute("aria-selected", tab.dataset.filter === filtro);
        });

        rows.forEach(row => {
            row.classList.toggle("hidden", row.dataset.aba !== filtro);
        });
    }

    if (tabs.length && rows.length) {
        tabs.forEach(tab => tab.addEventListener("click", () => ativarAba(tab.dataset.filter)));
        ativarAba("pendentes");
    }

    /* ============================================================
       2) MODAIS
    ============================================================ */
    const modalAvaliacao = document.getElementById("modalAvaliacao");
    const modalConteudo  = document.getElementById("modalConteudo");

    window.abrirModalAvaliacao = id => {
        modalAvaliacao.classList.remove("hidden");
        modalConteudo.textContent = "Carregando...";

        fetch(`/modulos/planos_acao/gestor_tarefas_avaliar.php?id_tarefa=${id}&id_plano=${ID_PLANO}`)
            .then(r => r.text())
            .then(html => {
                modalConteudo.innerHTML = html;

            // Executa scripts embutidos no HTML carregado
            modalConteudo.querySelectorAll("script").forEach(script => {
                const novo = document.createElement("script");
                if (script.src) {
                    novo.src = script.src;
                } else {
                    novo.textContent = script.textContent;
                }
                document.body.appendChild(novo);
            });

            // Agora sim, inicializa o modal
            if (typeof window.inicializarAvaliacao === "function") {
                window.inicializarAvaliacao();
            }

            })
            .catch(() => modalConteudo.textContent = "Erro ao carregar.");
    };

    window.fecharModalAvaliacao = () => {
        modalAvaliacao.classList.add("hidden");
    };

    /* ============================================================
       3) DETALHES E REABERTA (inalterado)
    ============================================================ */
    window.abrirDetalhesTarefa = id => {
        const modal = document.getElementById("modalDetalhesTarefa");
        const conteudo = document.getElementById("conteudoDetalhesTarefa");

        modal.classList.remove("hidden");
        conteudo.textContent = "Carregando...";

        fetch(`/modulos/planos_acao/planos_acao_tarefas_detalhes.php?id=${id}`)
            .then(r => r.text())
            .then(html => conteudo.innerHTML = html)
            .catch(() => conteudo.textContent = "Erro ao carregar.");
    };

    window.fecharModalDetalhes = () => {
        document.getElementById("modalDetalhesTarefa").classList.add("hidden");
    };

    window.abrirDetalhesReaberta = id => {
        const modal = document.getElementById("modalReaberta");
        const conteudo = document.getElementById("conteudoReaberta");

        modal.classList.remove("hidden");
        conteudo.textContent = "Carregando...";

        fetch(`/modulos/planos_acao/tarefa_reaberta_detalhes.php?id=${id}`)
            .then(r => r.text())
            .then(html => conteudo.innerHTML = html)
            .catch(() => conteudo.textContent = "Erro ao carregar.");
    };

    window.fecharModalReaberta = () => {
        document.getElementById("modalReaberta").classList.add("hidden");
    };

});
