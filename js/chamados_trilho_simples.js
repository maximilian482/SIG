// ============================
// ABRIR MODAL DE DETALHES
// ============================
document.addEventListener("click", async (e) => {

    const btn = e.target.closest(".btn-detalhes-simples");
    if (!btn) return;

    const id = btn.dataset.id;

    // Busca os dados do chamado
    const res = await fetch(`/ajax/trilho_detalhes_simples.php?id=${id}`);
    const dados = await res.json();

    // Preenche textos
    document.getElementById("ds-titulo").innerText       = dados.tipo;
    document.getElementById("ds-origem").innerText       = dados.origem;
    document.getElementById("ds-destino").innerText      = dados.destino;
    document.getElementById("ds-responsavel").innerText  = dados.responsavel;
    document.getElementById("ds-observacoes").innerText  = dados.observacoes;

    // Ajusta rótulos conforme ação
    if (dados.acao === "enviar") {
        document.getElementById("lbl-origem").innerText      = "Origem:";
        document.getElementById("lbl-destino").innerText     = "Destino:";
        document.getElementById("lbl-responsavel").innerText = "Aos cuidados de:";
    } else {
        document.getElementById("lbl-origem").innerText      = "Enviado por:";
        document.getElementById("lbl-destino").innerText     = "Recebido por:";
        document.getElementById("lbl-responsavel").innerText = "Responsável:";
    }

    // ============================
    // BOTÕES DE AÇÃO NO MODAL
    // ============================
   document.getElementById("ds-acoes").innerHTML = "";


    // Abre o modal
    document.getElementById("modalDetalhesSimples").style.display = "block";
});


// ============================
// FECHAR MODAL
// ============================
document.addEventListener("click", (e) => {
    if (e.target.matches(".fechar-simples")) {
        document.getElementById("modalDetalhesSimples").style.display = "none";
    }
});


// ============================
// FUNÇÃO EXCLUIR
// ============================
function excluirTrilhoSimples(id) {

    if (!confirm("Deseja realmente excluir este registro?")) return;

    fetch(`/ajax/trilho_excluir.php?id=${id}`)
        .then(r => r.text())
        .then(() => {
            alert("Registro excluído com sucesso!");
            document.getElementById("modalDetalhesSimples").style.display = "none";

            // Atualiza a lista de abertos
            if (typeof atualizarAbertos === "function") {
                atualizarAbertos();
            }
        });
}
