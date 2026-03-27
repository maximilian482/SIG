console.log("JS carregado!");

// ===============================
// AVALIAÇÃO (MODAL ISOLADO)
// ===============================
function abrirModalAvaliacaoChamado(id) {
    const modal = document.getElementById('modalAvaliacaoChamado');
    if (!modal) return console.error("Modal de avaliação não encontrado!");

    const idInput = document.getElementById('modalAvaliacaoChamadoId');
    const tipoInput = document.getElementById('modalAvaliacaoChamadoTipo');
    const justificativaInput = document.getElementById('modalAvaliacaoChamadoJustificativa');
    const estrelasInput = document.getElementById('modalAvaliacaoNotaEstrelas');

    if (!idInput || !tipoInput || !justificativaInput || !estrelasInput) {
        return console.error("Campos do modal de avaliação não encontrados!");
    }

    idInput.value = id;
    tipoInput.value = '';
    justificativaInput.value = '';
    estrelasInput.value = '';

    document.querySelectorAll(".modal-estrela").forEach(e => e.classList.remove("selecionada"));

    document.getElementById('modalJustificativaContainer').style.display = 'none';
    document.getElementById('modalAvaliacaoEstrelas').style.display = 'none';

    modal.style.display = 'block';
}

function fecharModalAvaliacaoChamado() {
    const modal = document.getElementById('modalAvaliacaoChamado');
    if (modal) modal.style.display = 'none';
}

function modalToggleJustificativa() {
    const valor = document.getElementById('modalAvaliacaoChamadoTipo').value;

    const estrelas = document.getElementById('modalAvaliacaoEstrelas');
    const justificativa = document.getElementById('modalJustificativaContainer');

    if (!estrelas || !justificativa) return;

    if (valor === "Sim") {
        estrelas.style.display = 'block';
        justificativa.style.display = 'none';
    } else if (valor === "Não") {
        estrelas.style.display = 'none';
        justificativa.style.display = 'block';
    } else {
        estrelas.style.display = 'none';
        justificativa.style.display = 'none';
    }
}

// ===============================
// SISTEMA DE ESTRELAS
// ===============================
document.addEventListener("DOMContentLoaded", () => {
    const estrelas = document.querySelectorAll(".modal-estrela");
    const inputNota = document.getElementById("modalAvaliacaoNotaEstrelas");

    estrelas.forEach(estrela => {
        estrela.addEventListener("click", function () {
            const valor = this.getAttribute("data-valor");
            inputNota.value = valor;

            estrelas.forEach(e => e.classList.remove("selecionada"));
            this.classList.add("selecionada");
        });
    });
});

// ===============================
// ENVIAR AVALIAÇÃO
// ===============================
function enviarAvaliacaoChamado(event) {
    event.preventDefault();

    const id = document.getElementById('modalAvaliacaoChamadoId').value;
    const resposta = document.getElementById('modalAvaliacaoChamadoTipo').value;
    const justificativa = document.getElementById('modalAvaliacaoChamadoJustificativa').value;
    const notaEstrelas = document.getElementById('modalAvaliacaoNotaEstrelas').value;
    const tipo = document.getElementById("modalAvaliacaoChamadoTipo").value;
        const nota = document.getElementById("modalAvaliacaoNotaEstrelas").value;

        if (tipo === "Sim" && !nota) {
            mostrarMensagem("Selecione uma nota de 1 a 5.", "erro");
            event.preventDefault();
            return;
        }


    if (!resposta) return mostrarMensagem("❌ Selecione se você foi atendido.", "erro");
    if (resposta === "Sim" && !notaEstrelas) return mostrarMensagem("❌ Selecione uma nota de 1 a 5.", "erro");
    if (resposta === "Não" && justificativa.trim() === "") return mostrarMensagem("❌ Explique o motivo.", "erro");

    const dados = new URLSearchParams({
        id,
        avaliacao: resposta,
        nota_estrelas: notaEstrelas,
        justificativa
    });

    fetch('chamados_salvar_avaliacao.php', {
        method: 'POST',
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: dados
    })
    .then(r => r.text())
    .then(msg => {
        mostrarMensagem(msg, "sucesso");
        fecharModalAvaliacaoChamado();
        setTimeout(() => location.reload(), 1500);
    })
    .catch(err => {
        mostrarMensagem("❌ Erro ao enviar avaliação.", "erro");
        console.error(err);
    });
}


// ===============================
// DETALHES DO CHAMADO
// ===============================
function abrirModalDetalhesChamado(id) {
    const modal = document.getElementById('modalDetalhesChamado');
    const conteudo = document.getElementById('conteudoDetalhesChamado');

    if (!modal || !conteudo) {
        console.error("Modal de detalhes não encontrado!");
        return;
    }

    conteudo.innerHTML = 'Carregando...';

    fetch('../modulos/chamados_detalhes.php?id=' + id)
        .then(r => r.text())
        .then(html => {
            conteudo.innerHTML = html;
            modal.style.display = 'block';
        })
        .catch(err => {
            conteudo.innerHTML = 'Erro ao carregar detalhes.';
            console.error(err);
        });
}

function fecharModalDetalhesChamado() {
    const modal = document.getElementById('modalDetalhesChamado');
    if (modal) modal.style.display = 'none';
}

// ===============================
// FECHAR AO CLICAR FORA
// ===============================
window.addEventListener("click", function(event) {
    const modalDet = document.getElementById('modalDetalhesChamado');
    const modalAval = document.getElementById('modalAvaliacaoChamado');

    if (event.target === modalDet) fecharModalDetalhesChamado();
    if (event.target === modalAval) fecharModalAvaliacaoChamado();
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
document.addEventListener('keydown', function(e) {
    if (e.key === "Escape") {
        fecharModalAvaliacaoChamado();
        fecharModalDetalhesChamado();
    }
});

