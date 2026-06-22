document.addEventListener("click", function(e) {

    // ===============================
    // ABRIR / FECHAR DETALHES
    // ===============================
    if (e.target.classList.contains("btn-detalhes")) {

        const id = e.target.dataset.id;

        const linhaPrincipal = e.target.closest("tr");
        const linhaDetalhes = linhaPrincipal.nextElementSibling;
        const conteudo = linhaDetalhes.querySelector(".detalhes-conteudo");

        // Se já está aberto → fechar
        if (!linhaDetalhes.classList.contains("oculto")) {
            linhaDetalhes.classList.add("oculto");
            conteudo.innerHTML = "";
            return;
        }

        // Carregar detalhes via AJAX
        fetch(`/ajax/auditoria_pp_detalhes.php?id=${id}`)
            .then(res => res.text())
            .then(html => {
                conteudo.innerHTML = html;
                linhaDetalhes.classList.remove("oculto");
            });
    }

    // ===============================
    // EXCLUIR AUDITORIA
    // ===============================
    if (e.target.classList.contains("btn-excluir")) {

        if (!confirm("Tem certeza que deseja excluir esta auditoria?")) return;

        const id = e.target.dataset.id;

        fetch(`/ajax/auditoria_pp_excluir.php?id=${id}`)
            .then(res => res.text())
            .then(ret => {

                // Remover linha principal e linha de detalhes
                const linha = e.target.closest("tr");
                const linhaDetalhes = linha.nextElementSibling;

                linhaDetalhes.remove();
                linha.remove();
            });
    }

});
