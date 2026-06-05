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
                <p><strong>Categoria:</strong> ${data.categoria_nome}</p>
                <p><strong>Nome:</strong> ${data.nome}</p>
                <p><strong>Descrição:</strong> ${data.descricao || '—'}</p>
                <p><strong>Setor:</strong> ${data.setor}</p>
                <p><strong>Valor:</strong> R$ ${parseFloat(data.valor).toFixed(2)}</p>
                <p><strong>Loja:</strong> ${data.loja_nome}</p>
                <p><strong>Responsável:</strong> ${data.responsavel_nome}</p>
                <p><strong>Data Registro:</strong> ${
                    data.data_registro
                        ? new Date(data.data_registro).toLocaleDateString('pt-BR')
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
// Modal de Baixa (Inativar Item)
// ===============================
function abrirModal(id, nome) {
    document.getElementById('modalId').value = id;
    document.getElementById('modalTexto').innerHTML =
        `Deseja dar baixa no item <strong>${nome}</strong>?`;

    document.getElementById('modalInativar').style.display = 'block';
}

function fecharModal() {
    document.getElementById('modalInativar').style.display = 'none';
}
