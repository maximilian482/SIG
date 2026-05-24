/* ============================================================
   ABRIR / FECHAR MODAL
============================================================ */
function abrirModal() {
    document.getElementById("modalRegistro").style.display = "flex";
}

function fecharModal() {
    document.getElementById("modalRegistro").style.display = "none";
}

/* ============================================================
   EXPANDIR DETALHES
============================================================ */
function toggleDetalhes(id) {
    const linha = document.getElementById("detalhes-" + id);
    linha.style.display = linha.style.display === "table-row" ? "none" : "table-row";
}

/* ============================================================
   SISTEMA DE MENSAGENS PREMIUM (VERSÃO OTIMIZADA)
============================================================ */
function mostrarMensagemPremium(texto, icone = "ℹ️") {
    const overlay = document.getElementById("overlayMensagem");
    const box = document.getElementById("mensagemTopo");
    const iconeBox = document.getElementById("iconeMensagem");
    const textoBox = document.getElementById("textoMensagem");

    iconeBox.innerHTML = icone;
    textoBox.innerHTML = texto;

    overlay.style.display = "block";
    box.style.display = "block";

    // Fade-in suave
    setTimeout(() => {
        box.style.opacity = "1";
    }, 20);

    // Fechar ao clicar fora
    overlay.onclick = () => fecharMensagemPremium();

    // Fechar ao clicar na própria mensagem
    box.onclick = () => fecharMensagemPremium();

    // Fechar automático mais rápido (2.5s)
    setTimeout(() => {
        fecharMensagemPremium();
    }, 2500);
}

function fecharMensagemPremium() {
    const overlay = document.getElementById("overlayMensagem");
    const box = document.getElementById("mensagemTopo");

    // Fade-out
    box.style.opacity = "0";

    setTimeout(() => {
        overlay.style.display = "none";
        box.style.display = "none";
    }, 250);
}


/* ============================================================
   CONFIRMAÇÃO PREMIUM
============================================================ */
function confirmarExclusaoPremium(callback) {
    mostrarMensagemPremium(
        "Tem certeza que deseja excluir este registro?<br><br><b>Esta ação não pode ser desfeita.</b>",
        "⚠️"
    );

    const box = document.getElementById("mensagemTopo");

    const botoes = document.createElement("div");
    botoes.style.marginTop = "20px";

    botoes.innerHTML = `
        <button id="btnConfirmar" style="
            padding:10px 18px;
            margin-right:10px;
            background:#e74c3c;
            color:white;
            border:none;
            border-radius:6px;
            cursor:pointer;
            font-weight:bold;
        ">Excluir</button>

        <button id="btnCancelar" style="
            padding:10px 18px;
            background:#7f8c8d;
            color:white;
            border:none;
            border-radius:6px;
            cursor:pointer;
            font-weight:bold;
        ">Cancelar</button>
    `;

    box.appendChild(botoes);

    document.getElementById("btnConfirmar").onclick = () => {
        fecharMensagemPremium();
        callback(true);
    };

    document.getElementById("btnCancelar").onclick = () => {
        fecharMensagemPremium();
        callback(false);
    };
}

/* ============================================================
   EXCLUIR – PERMISSÃO + CONFIRMAÇÃO
============================================================ */
document.addEventListener("click", function (e) {
    const botao = e.target.closest("a.btn-acao.excluir");
    if (!botao) return;

    e.preventDefault();
    e.stopPropagation();

    const registradoPor = botao.dataset.registrado;
    const cpfLogado = document.querySelector(".controlados-container").dataset.cpf;

    if (registradoPor !== cpfLogado) {
        mostrarMensagem("Somente o criador do registro pode excluir.", "erro");
        return;
    }

    if (confirm("Tem certeza que deseja excluir este registro?")) {
        window.location.href = botao.href;
    }
});

/* ============================================================
   EDITAR – PERMISSÃO + NAVEGAÇÃO LIMPA
============================================================ */
document.addEventListener("click", function (e) {
    const link = e.target.closest("a.btn-acao.editar");
    if (!link) return;

    e.preventDefault();
    e.stopPropagation();

    const registradoPor = link.dataset.registrado;
    const cpfLogado = document.querySelector(".controlados-container").dataset.cpf;

    if (registradoPor !== cpfLogado) {
        mostrarMensagem("Somente o criador do registro pode editar.", "erro");
        return;
    }

    window.location.href = link.href;
});




// Comportamento do Modal
function abrirModal() {
    document.getElementById("modalRegistro").style.display = "flex";
}

function fecharModal() {
    document.getElementById("modalRegistro").style.display = "none";
}

// Fechar clicando fora
window.addEventListener("click", function(e) {
    const modal = document.getElementById("modalRegistro");
    if (e.target === modal) fecharModal();
});

// Fechar com ESC
document.addEventListener("keydown", function(e) {
    if (e.key === "Escape") fecharModal();
});
