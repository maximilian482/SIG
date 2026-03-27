console.log("JS LOJA carregado!");

// ===============================
// DETALHES (ABERTOS)
// ===============================
function abrirDetalhesChamado(id) {
    const modal = document.getElementById('modalDetalhesChamado');
    const conteudo = document.getElementById('conteudoDetalhesChamado');

    if (!modal || !conteudo) {
        console.error("Modal de detalhes (abertos) não encontrado!");
        return;
    }

    conteudo.innerHTML = "Carregando...";

    fetch("chamados_detalhes.php?id=" + id)
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

function fecharModalDetalhesChamado() {
    const modal = document.getElementById('modalDetalhesChamado');
    if (modal) modal.style.display = "none";
}

// ===============================
// DETALHES (ENCERRADOS)
// ===============================
function abrirDetalhesEncerrado(id) {
    const modal = document.getElementById('modalDetalhesEncerrado');
    const conteudo = document.getElementById('conteudoDetalhesEncerrado');

    if (!modal || !conteudo) {
        console.error("Modal de detalhes (encerrados) não encontrado!");
        return;
    }

    conteudo.innerHTML = "Carregando...";

    fetch("chamados_detalhes.php?id=" + id)
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

function fecharDetalhesEncerrado() {
    const modal = document.getElementById('modalDetalhesEncerrado');
    if (modal) modal.style.display = "none";
}

// ===============================
// FECHAMENTO
// ===============================
function abrirModalFecharChamado(id) {
    document.getElementById("fecharChamadoId").value = id;
    document.getElementById("fecharChamadoSolucao").value = "";
    document.getElementById("modalFecharChamado").style.display = "flex";
}

function fecharModalFecharChamado() {
    document.getElementById("modalFecharChamado").style.display = "none";
}

function enviarFechamentoChamado(event) {
    event.preventDefault();

    const id = document.getElementById("fecharChamadoId").value;
    const solucao = document.getElementById("fecharChamadoSolucao").value;

    if (!solucao.trim()) {
        mostrarMensagem("Descreva a solução aplicada.", "aviso");
        return;
    }

    fetch("chamados_salvar_fechamento_loja.php", {
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

        fecharModalFecharChamado();
        setTimeout(() => location.reload(), 1200);
    })
    .catch(err => console.error(err));
}

// ===============================
// CLICK FORA + ESC
// ===============================
window.addEventListener("click", function(event) {
    const modais = [
        "modalDetalhesChamado",
        "modalFecharChamado",
        "modalDetalhesEncerrado"
    ];

    modais.forEach(id => {
        const modal = document.getElementById(id);
        if (modal && event.target === modal) {
            modal.style.display = "none";
        }
    });
});

document.addEventListener("keydown", function(e) {
    if (e.key === "Escape") {
        fecharModalDetalhesChamado();
        fecharModalFecharChamado();
        fecharDetalhesEncerrado();
    }
});
