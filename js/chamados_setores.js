console.log("JS setores carregado!");

// ===============================
// ABRIR / FECHAR — DETALHES
// ===============================
function abrirModalDetalhes(id) {
    const modal = document.getElementById('modalDetalhes');
    const conteudo = document.getElementById('modalDetalhesConteudo');

    if (!modal || !conteudo) {
        console.error("Modal de detalhes não encontrado!");
        return;
    }

    conteudo.innerHTML = "Carregando...";

    fetch("chamados_detalhes.php?id=" + id, {
        headers: { "X-Requested-With": "XMLHttpRequest" }
    })
    .then(r => r.text())
    .then(html => {
        conteudo.innerHTML = html;
        modal.style.display = "flex";
    })
    .catch(err => {
        conteudo.innerHTML = "Erro ao carregar detalhes.";
        console.error(err);
    });
}

function fecharModalDetalhes() {
    const modal = document.getElementById('modalDetalhes');
    if (modal) modal.style.display = "none";
}

// ===============================
// ABRIR / FECHAR — FECHAR CHAMADO
// ===============================
function abrirModalFechar(id) {
    document.getElementById("fecharId").value = id;
    document.getElementById("modalFechar").style.display = "flex";
}

function fecharModalFechar() {
    document.getElementById("modalFechar").style.display = "none";
}

// ===============================
// ABRIR / FECHAR — REABRIR CHAMADO
// ===============================
function abrirModalReabrir(id) {
    document.getElementById("reabrirId").value = id;
    document.getElementById("modalReabrir").style.display = "flex";
}

function fecharModalReabrir() {
    document.getElementById("modalReabrir").style.display = "none";
}

// ===============================
// FECHAR AO CLICAR FORA
// ===============================
window.addEventListener("click", function(event) {
    const modais = [
        "modalDetalhes",
        "modalFechar",
        "modalReabrir"
    ];

    modais.forEach(id => {
        const modal = document.getElementById(id);
        if (modal && event.target === modal) {
            modal.style.display = "none";
        }
    });
});

// ===============================
// IMPEDIR FECHAMENTO AO CLICAR DENTRO
// ===============================
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('.modal-conteudo').forEach(box => {
        box.addEventListener('click', e => e.stopPropagation());
    });
});

// ===============================
// FECHAR COM ESC
// ===============================
document.addEventListener("keydown", function(e) {
    if (e.key === "Escape") {
        fecharModalDetalhes();
        fecharModalFechar();
        fecharModalReabrir();
    }
});

// ===============================
// ENVIAR FECHAMENTO
// ===============================
function enviarFechamento(event) {
    event.preventDefault();

    const id = document.getElementById("fecharId").value;
    const solucao = document.getElementById("fecharSolucao").value;

    if (!solucao.trim()) {
        mostrarMensagem("Descreva a solução aplicada.", "aviso");
        return;
    }

    fetch("chamados_salvar_fechamento_setores.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ id, solucao })
    })
    .then(r => r.text())
    .then(msg => {
        try {
            const data = JSON.parse(msg);
            mostrarMensagem(data.message, data.success ? "sucesso" : "erro");
        } catch {
            mostrarMensagem("Erro inesperado ao fechar chamado.", "erro");
        }

        fecharModalFechar();
        setTimeout(() => location.reload(), 1200);
    })
    .catch(err => console.error(err));
}

// ===============================
// ENVIAR REABERTURA
// ===============================
function enviarReabertura(event) {
    event.preventDefault();

    const id = document.getElementById("reabrirId").value;
    const motivo = document.getElementById("reabrirMotivo").value;

    if (!motivo.trim()) {
        mostrarMensagem("Informe o motivo da reabertura.", "aviso");
        return;
    }

    fetch("chamados_salvar_reabertura_setores.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ id, motivo })
    })
    .then(r => r.text())
    .then(msg => {
        try {
            const data = JSON.parse(msg);
            mostrarMensagem(data.message, data.success ? "sucesso" : "erro");
        } catch {
            mostrarMensagem("Erro inesperado ao reabrir chamado.", "erro");
        }

        fecharModalReabrir();
        setTimeout(() => location.reload(), 1200);
    })
    .catch(err => console.error(err));
}

// ===============================
// LISTENERS — DETALHES / FECHAR
// ===============================
document.addEventListener("DOMContentLoaded", () => {

    // Botão: Ver detalhes
    document.querySelectorAll(".btn-ver-detalhes").forEach(btn => {
        btn.addEventListener("click", () => {
            abrirModalDetalhes(btn.dataset.id);
        });
    });

    // Botão: Fechar chamado
    document.querySelectorAll(".btn-fechar-chamado").forEach(btn => {
        btn.addEventListener("click", () => {
            abrirModalFechar(btn.dataset.id);
        });
    });

    // ===============================
    // ABAS
    // ===============================
    const tabs = document.querySelectorAll(".tab");
    const grupos = document.querySelectorAll(".grupo");

    tabs.forEach(tab => {
        tab.addEventListener("click", () => {
            const filtro = tab.dataset.filter;

            // Atualiza seleção visual
            tabs.forEach(t => t.setAttribute("aria-selected", "false"));
            tab.setAttribute("aria-selected", "true");

            // Mostra/esconde grupos
            grupos.forEach(g => {
                if (g.dataset.grupo === filtro) {
                    g.classList.remove("hidden");
                } else {
                    g.classList.add("hidden");
                }
            });
        });
    });

});
