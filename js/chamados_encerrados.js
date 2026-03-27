// ===============================
// ABRIR MODAL DE DETALHES
// ===============================
window.abrirDetalhes = function(id) {
    const modal = document.getElementById("modalDetalhes");
    const conteudo = document.getElementById("conteudoDetalhes");

    if (!modal || !conteudo) return;

    conteudo.innerHTML = "Carregando...";

    fetch("chamados_detalhes.php?id=" + encodeURIComponent(id))
        .then(r => r.text())
        .then(html => {
            conteudo.innerHTML = html;
            modal.style.display = "block";
        })
        .catch(() => {
            conteudo.innerHTML = "Erro ao carregar detalhes.";
        });
};

// ===============================
// FECHAR MODAL
// ===============================
window.fecharDetalhes = function() {
    const modal = document.getElementById("modalDetalhes");
    if (modal) modal.style.display = "none";
};

// ===============================
// FECHAR AO CLICAR FORA DO MODAL
// ===============================
window.addEventListener("click", function(event) {
    const modal = document.getElementById("modalDetalhes");
    if (modal && event.target === modal) {
        modal.style.display = "none";
    }
});

// ===============================
// FECHAR COM A TECLA ESC
// ===============================
window.addEventListener("keydown", function(event) {
    if (event.key === "Escape") {
        const modal = document.getElementById("modalDetalhes");
        if (modal) modal.style.display = "none";
    }
});
