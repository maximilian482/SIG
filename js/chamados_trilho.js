document.addEventListener("DOMContentLoaded", () => {

    // ============================
    // TROCA DE ABAS
    // ============================
    document.querySelectorAll(".aba").forEach(btn => {
        btn.addEventListener("click", () => {

            document.querySelectorAll(".aba").forEach(b => b.classList.remove("ativa"));
            document.querySelectorAll(".conteudo-aba").forEach(c => c.classList.remove("ativo"));

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
        if (!modal || !conteudo) return;

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

    if (fechar) {
        fechar.addEventListener("click", fecharDetalhes);
    }

    window.addEventListener("click", (e) => {
        if (e.target === modal) fecharDetalhes();
    });

    document.addEventListener("click", (e) => {
        const btn = e.target.closest(".btn-detalhes");
        if (!btn) return;

        abrirDetalhes(btn.dataset.id);
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
// FATURAR — ABRIR MODAL
// ============================
let idParaFaturar = null;

document.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-faturar");
    if (!btn) return;

    idParaFaturar = btn.dataset.id;

    document.getElementById("notaTransferencia").value = "";
    document.getElementById("modalFaturar").style.display = "flex";
});

// ============================
// FECHAR MODAL (botão X)
// ============================
document.getElementById("fecharModalFaturar").onclick = () => {
    document.getElementById("modalFaturar").style.display = "none";
};

// ============================
// FECHAR MODAL (botão cancelar)
// ============================
document.getElementById("btnCancelarFaturar").onclick = () => {
    document.getElementById("modalFaturar").style.display = "none";
};

// ============================
// FECHAR MODAL (clicar fora)
// ============================
window.addEventListener("click", (e) => {
    const modal = document.getElementById("modalFaturar");
    if (e.target === modal) modal.style.display = "none";
});

// ============================
// CONFIRMAR FATURAMENTO
// ============================
document.getElementById("btnConfirmarFaturar").onclick = () => {

    const nota = document.getElementById("notaTransferencia").value.trim();

    if (!nota) {
    mostrarMensagem("Informe o número da nota de transferência.", "erro");
    document.getElementById("modalFaturar").style.display = "none";
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

    document.getElementById("modalFaturar").style.display = "none";
};






    // ============================
    // COLETAR PROTOCOLO
    // ============================
    document.addEventListener("click", (e) => {
        const btn = e.target.closest(".btn-coletar");
        if (!btn) return;

        const id = btn.dataset.id;

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
    // FINALIZAR ENTREGA (MOTOBOY)
    // ============================
    document.addEventListener("click", (e) => {
        const btn = e.target.closest(".btn-entregar");
        if (!btn) return;

        const id = btn.dataset.id;

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

});
