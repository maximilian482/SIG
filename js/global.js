function mostrarMensagem(msg, tipo = "sucesso") {

    const overlay = document.getElementById("overlayMensagem");
    const box = document.getElementById("mensagemTopo");
    const texto = document.getElementById("textoMensagem");
    const icone = document.getElementById("iconeMensagem");

    const icones = {
        sucesso: "✔️",
        erro: "❌",
        aviso: "⚠️"
    };

    icone.innerText = icones[tipo] || "ℹ️";
    texto.innerText = msg;

    if (tipo === "sucesso") {
        box.style.background = "var(--verde-palmeiras-claro)";
        box.style.color = "white";
    } else if (tipo === "erro") {
        box.style.background = "var(--erro-bg)";
        box.style.color = "var(--erro-texto)";
    } else if (tipo === "aviso") {
        box.style.background = "var(--warning-bg)";
        box.style.color = "var(--warning-texto)";
    }

    overlay.style.display = "block";
    box.style.display = "block";

    setTimeout(() => {
        box.style.opacity = "1";
    }, 10);

    setTimeout(() => {
        box.style.opacity = "0";

        setTimeout(() => {
            overlay.style.display = "none";
            box.style.display = "none";
        }, 400);

    }, 5000);
}

  /* ============================================================
   FECHAR MODAL PELO X
============================================================ */
document.addEventListener("click", e => {
    if (!e.target.classList.contains("plano-modal-close")) return;

    const modal = e.target.closest(".plano-modal");
    if (modal) modal.classList.add("hidden");
});


/* ============================================================
   FECHAR MODAL CLICANDO FORA
============================================================ */
document.addEventListener("click", e => {
    const modal = e.target.closest(".plano-modal");
    if (!modal) return;

    // clicou no fundo (overlay)
    if (e.target === modal) {
        modal.classList.add("hidden");
    }
});


/* ============================================================
   FECHAR MODAL COM ESC
============================================================ */
document.addEventListener("keydown", e => {
    if (e.key !== "Escape") return;

    document.querySelectorAll(".plano-modal").forEach(modal => {
        modal.classList.add("hidden");
    });
});
