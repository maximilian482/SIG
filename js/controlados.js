/* ============================================================
   EXPANDIR DETALHES
============================================================ */
function toggleDetalhes(id) {
    const linha = document.getElementById("detalhes-" + id);
    linha.style.display = linha.style.display === "table-row" ? "none" : "table-row";
}

/* ============================================================
   EXCLUIR – PERMISSÃO
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
   EDITAR – PERMISSÃO
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
