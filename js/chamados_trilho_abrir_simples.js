document.addEventListener("DOMContentLoaded", () => {

    const form = document.querySelector(".form-chamado");
    if (!form) return;

    form.addEventListener("submit", (e) => {

        const origem = document.getElementById("loja_origem").value;
        const destino = document.getElementById("loja_destino").value;
        const tipo = document.getElementById("tipo").value;
        const responsavel = document.getElementById("responsavel_id").value;
        const descricao = document.getElementById("descricao").value;

        // ============================
        // VALIDAÇÃO: origem ≠ destino
        // ============================
        if (origem === destino) {
            e.preventDefault();
            mostrarMensagem("A loja de origem e destino não podem ser iguais.", "erro");
            return;
        }

        // ============================
        // VALIDAÇÃO DE CAMPOS
        // ============================
        if (!tipo || !origem || !destino || !responsavel || !descricao.trim()) {
            e.preventDefault();
            mostrarMensagem("Preencha todos os campos obrigatórios.", "erro");
            return;
        }
    });

});
