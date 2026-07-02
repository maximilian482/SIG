/* ============================================================
   MODAL GLOBAL — COMPATÍVEL COM .modal-custom
============================================================ */

// Abre modal
function abrirModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.style.display = "flex";
    modal.dataset.open = "true";
}

// Fecha modal
function fecharModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.style.display = "none";
    delete modal.dataset.open;
}

/* ============================================================
   FECHAR MODAL PELO BOTÃO X (.modal-close)
============================================================ */
document.addEventListener("click", e => {
    if (!e.target.classList.contains("modal-close")) return;

    const modal = e.target.closest(".modal-custom");
    if (modal) {
        fecharModal(modal.id);
    }
});

/* ============================================================
   FECHAR MODAL CLICANDO FORA
============================================================ */
document.addEventListener("click", e => {
    const modal = e.target.closest(".modal-custom");

    if (modal && e.target === modal) {
        fecharModal(modal.id);
    }
});

/* ============================================================
   FECHAR MODAL COM ESC
============================================================ */
document.addEventListener("keydown", e => {
    if (e.key !== "Escape") return;

    document.querySelectorAll(".modal-custom[data-open='true']")
        .forEach(modal => fecharModal(modal.id));
});
