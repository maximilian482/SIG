document.addEventListener("DOMContentLoaded", () => {

    // ============================
    // TROCA DE ABAS
    // ============================
    document.querySelectorAll(".aba").forEach(btn => {
        btn.addEventListener("click", () => {

            // Remove ativo de todas as abas
            document.querySelectorAll(".aba").forEach(b => b.classList.remove("ativa"));
            document.querySelectorAll(".conteudo-aba").forEach(c => c.classList.remove("ativo"));

            // Ativa a aba clicada
            btn.classList.add("ativa");
            const alvo = document.getElementById(btn.dataset.aba);
            if (alvo) alvo.classList.add("ativo");
        });
    });



    // ============================
    // MODAL DE DETALHES
    // ============================
    const modal = document.getElementById("modalDetalhes");
    const conteudo = document.getElementById("modal-body-detalhes");
    const fechar = document.querySelector(".modal-fechar");

    function abrirDetalhes(id) {
        if (!modal || !conteudo) {
            console.error("Modal ou conteúdo não encontrado.");
            return;
        }

        modal.style.display = "flex";
        conteudo.innerHTML = "Carregando...";

        fetch("/modulos/chamados_trilho_detalhes.php?id=" + id)
            .then(r => r.text())
            .then(html => conteudo.innerHTML = html)
            .catch(() => conteudo.innerHTML = "<p style='color:red;'>Erro ao carregar detalhes.</p>");
    }

    function fecharDetalhes() {
        if (modal) modal.style.display = "none";
    }

    // Botão fechar
    if (fechar) {
        fechar.addEventListener("click", fecharDetalhes);
    }

    // Fechar clicando fora
    window.addEventListener("click", (e) => {
        if (e.target === modal) fecharDetalhes();
    });

    // Delegação para botão DETALHES
    document.addEventListener("click", (e) => {
        const btn = e.target.closest(".btn-detalhes");
        if (!btn) return;

        const id = btn.dataset.id;
        if (!id) return;

        abrirDetalhes(id);
    });



    // ============================
    // (RESERVADO) AÇÕES FUTURAS
    // Coletar, entregar, cancelar etc.
    // ============================
    // Aqui você pode adicionar outras ações do trilho
    // usando a mesma estrutura de delegação:
    //
    // document.addEventListener("click", (e) => {
    //     const btn = e.target.closest(".btn-coletar");
    //     if (!btn) return;
    //     const id = btn.dataset.id;
    //     // ação aqui...
    // });

});


// ===============================
// EXCLUIR PROTOCOLO
// ===============================
function excluirTrilho(id) {
    if (!confirm("Tem certeza que deseja excluir este protocolo?")) return;

    fetch("chamados_trilho_excluir.php", {
        method: "POST",
        body: new URLSearchParams({ id })
    })
    .then(r => r.text())
    .then(resp => {
        mostrarMensagem(resp, "sucesso");
        setTimeout(() => location.reload(), 1200);
    })
    .catch(() => {
        mostrarMensagem("Erro ao excluir protocolo.", "erro");
    });
}



// ===============================
// FECHAR MODAL AO CLICAR FORA
// ===============================
document.addEventListener("click", function(e) {
    const modal = document.getElementById("modalDetalhes");
    if (e.target === modal) {
        fecharDetalhes();
    }
});



