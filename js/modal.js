// ===============================
// MODAL GLOBAL
// ===============================

// Abre modal
function abrirModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.style.display = "flex";
    modal.setAttribute("data-open", "true");
}

// Fecha modal
function fecharModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.style.display = "none";
    modal.removeAttribute("data-open");
}

// Fecha ao clicar fora
document.addEventListener("click", function (e) {
    const modais = document.querySelectorAll(".modal-custom[data-open='true']");

    modais.forEach(modal => {
        const conteudo = modal.querySelector(".modal-custom-content");

        if (!conteudo.contains(e.target)) {
            modal.style.display = "none";
            modal.removeAttribute("data-open");
        }
    });
});

// Fecha com ESC
document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
        const modais = document.querySelectorAll(".modal-custom[data-open='true']");
        modais.forEach(modal => {
            modal.style.display = "none";
            modal.removeAttribute("data-open");
        });
    }
});

// Botões de fechar com classe .modal-close
document.addEventListener("click", function (e) {
    if (e.target.classList.contains("modal-close")) {
        const modal = e.target.closest(".modal-custom");
        if (modal) {
            modal.style.display = "none";
            modal.removeAttribute("data-open");
        }
    }
});
