// ===============================
// Modal de Detalhes
// ===============================
function abrirDetalhes(id) {
    const modal = document.getElementById('modalDetalhes');
    const conteudo = document.getElementById('conteudoDetalhes');

    modal.style.display = 'block';
    conteudo.innerHTML = "Carregando...";

    fetch("detalhes_item_ajax.php?id=" + id)
        .then(res => res.json())
        .then(data => {
            let html = `
                <p><strong>Patrimônio:</strong> ${data.controle}</p>
                <p><strong>Nome:</strong> ${data.nome}</p>
                <p><strong>Descrição:</strong> ${data.descricao || '—'}</p>
                <p><strong>Setor:</strong> ${data.setor}</p>
                <p><strong>Valor:</strong> R$ ${parseFloat(data.valor).toFixed(2)}</p>
                <p><strong>Loja:</strong> ${data.loja_nome}</p>
                <p><strong>Responsável:</strong> ${data.responsavel_nome}</p>
                <p><strong>Motivo da baixa:</strong> ${data.motivo_baixa}</p>
                <p><strong>Data da baixa:</strong> ${
                    data.data_baixa
                        ? new Date(data.data_baixa).toLocaleDateString('pt-BR')
                        : '—'
                }</p>
            `;

            conteudo.innerHTML = html;
        })
        .catch(() => {
            conteudo.innerHTML = "<p style='color:red;'>Erro ao carregar detalhes.</p>";
        });
}

function fecharModalDetalhes() {
    document.getElementById('modalDetalhes').style.display = 'none';
}


// ===============================
// Modal de Reativação
// ===============================
function abrirModalReativar(id, nome) {
    document.getElementById('reativarId').value = id;
    document.getElementById('textoReativar').innerHTML =
        `Deseja reativar o item <strong>${nome}</strong>?<br>Selecione a nova loja abaixo.`;

    document.getElementById('modalReativar').style.display = 'block';
}

function fecharModalReativar() {
    document.getElementById('modalReativar').style.display = 'none';
}
