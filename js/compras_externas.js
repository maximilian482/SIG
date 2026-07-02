/* ============================================================
   CONFIGURAÇÃO GLOBAL
============================================================ */
const BASE = "/ajax/"; 



/* ============================================================
   ALTERAR TIPO DA COMPRA (COM NOTA / SEM NOTA)
============================================================ */
function alterarTipoCompra(id, tipo) {

    fetch(BASE + "compras_externas_alterar_tipo.php", {
        method: "POST",
        body: JSON.stringify({ id, tipo })
    })
    .then(r => r.json())
    .then(ret => {

        if (ret.sucesso) {

            // Atualiza o botão ativo
            document.querySelectorAll(".btn-tipo").forEach(btn => btn.classList.remove("ativo"));
            document.querySelector(`.btn-tipo[data-tipo="${tipo}"]`).classList.add("ativo");

            // Atualiza o label do anexo
            const label = document.getElementById("label_anexo");
            if (label) {
                label.textContent = tipo === "com_nota"
                    ? "Cupom / Nota Fiscal"
                    : "Print do Ajuste";
            }

            // Recarrega a página para mostrar os campos corretos
            location.reload();

        } else {
            mostrarMensagem(ret.erro, "erro");
        }
    })
    .catch(() => mostrarMensagem("Erro ao comunicar com o servidor.", "erro"));
}



/* ============================================================
   FINALIZAR COMPRA (FLUXO UNIFICADO)
============================================================ */
function finalizarCompra(id) {

    if (!confirm("Finalizar esta compra?")) return;

    const dados = new FormData();

    const tipo = document.querySelector(".btn-tipo.ativo").dataset.tipo;
    dados.append("id", id);
    dados.append("tipo_compra", tipo);

    // ============================================================
    // COM NOTA
    // ============================================================
    if (tipo === "com_nota") {

        dados.append("numero_nota", document.getElementById("numero_nota").value);
        dados.append("data_compra", document.getElementById("data_compra").value);
        dados.append("valor", document.getElementById("valor").value);
        dados.append("local_compra", document.getElementById("local_compra").value);
        dados.append("observacoes", document.getElementById("observacoes").value);

        // Anexos múltiplos
        const arquivos = document.getElementById("arquivo").files;
        for (let i = 0; i < arquivos.length; i++) {
            dados.append("anexos[]", arquivos[i]);
        }
    }

    // ============================================================
    // SEM NOTA
    // ============================================================
    if (tipo === "sem_nota") {

        dados.append("data_compra", document.getElementById("data_compra").value);
        dados.append("hora_ajuste", document.getElementById("hora_ajuste").value);
        dados.append("quantidade_ajustada", document.getElementById("quantidade_ajustada").value);
        dados.append("observacoes", document.getElementById("observacoes").value);

        // CUPOM
        const cupom = document.getElementById("arquivo_cupom").files[0];
        if (cupom) dados.append("arquivo_cupom", cupom);

        // PRINT DO AJUSTE
        const print = document.getElementById("arquivo_print").files[0];
        if (print) dados.append("arquivo_print", print);
    }

    // ============================================================
    // ENVIAR PARA O PHP
    // ============================================================
    fetch("/ajax/compras_externas_finalizar.php", {
        method: "POST",
        body: dados
    })
    .then(r => r.json())
    .then(ret => {

        if (ret.sucesso) {
            mostrarMensagem("Compra finalizada com sucesso!", "sucesso");
            setTimeout(() => window.location.href = "/modulos/compras_externas_gestao.php", 1200);
        } else {
            mostrarMensagem(ret.erro, "erro");
        }
    })
    .catch(() => mostrarMensagem("Erro ao comunicar com o servidor.", "erro"));
}






/* ============================================================
   EXCLUIR SOLICITAÇÃO
============================================================ */
function excluirSolicitacao(id) {

    if (!confirm("Tem certeza que deseja excluir esta solicitação?")) return;

    fetch(BASE + "compras_externas_excluir.php", {
        method: "POST",
        body: JSON.stringify({ id })
    })
    .then(r => r.json())
    .then(ret => {

        if (ret.sucesso) {
            mostrarMensagem("Solicitação excluída!", "sucesso");
            setTimeout(() => window.location.href = "/modulos/compras_externas_gestao.php", 1200);
        } else {
            mostrarMensagem(ret.erro, "erro");
        }
    })
    .catch(() => mostrarMensagem("Erro ao comunicar com o servidor.", "erro"));
}
