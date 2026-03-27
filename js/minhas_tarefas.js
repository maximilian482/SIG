// ===============================
// ABAS
// ===============================
const tabs   = document.querySelectorAll('.tab');
const grupos = document.querySelectorAll('.grupo');

tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        const filtro = tab.dataset.filter;

        tabs.forEach(t => t.setAttribute('aria-selected', 'false'));
        tab.setAttribute('aria-selected', 'true');

        grupos.forEach(g => {
            g.classList.toggle('hidden', g.dataset.grupo !== filtro);
        });
    });
});


// ===============================
// MODAL — MARCAR COMO FEITA
// ===============================
const modalFechar = document.getElementById('modalFecharTarefa');
const inputId    = document.getElementById('modal-id-tarefa');
const inputResp  = document.getElementById('modal-resposta');
const tituloSpan = document.getElementById('modal-titulo-tarefa');
const formModal  = document.getElementById('formFecharTarefa');
const contador   = document.getElementById('contador-resposta');

function abrirModalFecharTarefa(id, titulo) {
    inputId.value = id;
    inputResp.value = '';
    contador.textContent = '0';
    tituloSpan.textContent = 'Fechar tarefa: ' + titulo;

    modalFechar.classList.remove('hidden');
    inputResp.focus();
}

function fecharModalFecharTarefa() {
    modalFechar.classList.add('hidden');
}

// Fechar ao clicar no X
document.addEventListener("click", e => {
    if (e.target.classList.contains("plano-modal-close")) {
        fecharModalFecharTarefa();
    }
});

// Fechar ao clicar fora
document.addEventListener("click", e => {
    if (e.target === modalFechar) {
        fecharModalFecharTarefa();
    }
});

// Botões "Marcar como feita"
document.querySelectorAll('.btn-marcar-feita').forEach(btn => {
    btn.addEventListener('click', () => {
        abrirModalFecharTarefa(btn.dataset.id, btn.dataset.titulo || '');
    });
});

// Contador de caracteres
inputResp.addEventListener('input', () => {
    contador.textContent = inputResp.value.length.toString();
});

// Envio via AJAX
formModal.addEventListener('submit', async (e) => {
    e.preventDefault();

    const id = inputId.value.trim();
    const resposta = inputResp.value.trim();

    if (resposta.length < 5) {
        alert('A resposta é muito curta. Descreva melhor o que foi feito (mínimo 5 caracteres).');
        return;
    }

    try {
        const resp = await fetch('/modulos/planos_acao/tarefa_marcar_feita.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                id_tarefa: id,
                resposta: resposta
            })
        });

        if (!resp.ok) {
            alert('Ocorreu um erro ao fechar a tarefa. Tente novamente.');
            return;
        }

        location.reload();

    } catch (err) {
        alert('Falha de comunicação com o servidor. Tente novamente.');
    }
});


// ===============================
// MODAL — DETALHES DA TAREFA
// ===============================
function abrirDetalhesTarefa(id) {
    const modal = document.getElementById("modalDetalhesTarefa");
    const conteudo = document.getElementById("conteudoDetalhesTarefa");

    conteudo.innerHTML = "Carregando...";

    fetch("/modulos/planos_acao/planos_acao_tarefas_detalhes.php?id=" + id, {
        headers: { "X-Requested-With": "XMLHttpRequest" }
    })
    .then(r => r.text())
    .then(html => {
        conteudo.innerHTML = html;
        modal.classList.remove("hidden");
    });
}

function fecharDetalhesTarefa() {
    document.getElementById("modalDetalhesTarefa").classList.add("hidden");
}

// Fechar ao clicar no X
document.addEventListener("click", e => {
    if (e.target.classList.contains("plano-modal-close")) {
        fecharDetalhesTarefa();
    }
});

// Fechar ao clicar fora
document.addEventListener("click", e => {
    const modal = document.getElementById("modalDetalhesTarefa");
    if (e.target === modal) {
        fecharDetalhesTarefa();
    }
});

// Botões "Ver detalhes"
document.querySelectorAll('.btn-ver-tarefa').forEach(btn => {
    btn.addEventListener('click', () => {
        abrirDetalhesTarefa(btn.dataset.id);
    });
});
