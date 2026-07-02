// ===============================================
// GARANTE QUE O SISTEMA DE MENSAGENS DO LAYOUT EXISTE
// ===============================================
function esperarMensagemPronta(callback) {
    const check = setInterval(() => {
        if (document.getElementById("mensagemTopo")) {
            clearInterval(check);
            callback();
        }
    }, 50);
}

// ===============================================
// INÍCIO DO SCRIPT PRINCIPAL
// ===============================================
esperarMensagemPronta(() => {

    const lojaSelect = document.getElementById('loja_id');
    const itensContainer = document.getElementById('itens-container');
    const listaItens = document.getElementById('lista-itens');

    // ================================
    // 1. Carregar itens da loja
    // ================================
    lojaSelect.addEventListener('change', () => {
        const lojaId = lojaSelect.value;

        if (!lojaId) {
            itensContainer.classList.add('oculto');
            listaItens.innerHTML = '';
            return;
        }

        fetch(`/ajax/auditoria_checklist_config_listar.php?loja_id=${lojaId}`)
            .then(res => res.json())
            .then(lista => {

                listaItens.innerHTML = "";

                lista.forEach(item => {

                    const div = document.createElement("div");
                    div.classList.add("setor-item");

                    div.innerHTML = `
                        <div class="setor-esquerda">
                            <input type="checkbox" class="check-item" value="${item.id}" id="item_${item.id}" ${item.ativo ? "checked" : ""}>
                            <label for="item_${item.id}">${item.pergunta}</label>
                        </div>

                        <div class="setor-direita">
                            <button class="btn-edit" data-id="${item.id}">✏️</button>
                            <button class="btn-del" data-id="${item.id}">🗑️</button>
                        </div>
                    `;

                    listaItens.appendChild(div);
                });

                itensContainer.classList.remove("oculto");
            });

    });

    // ================================
    // 2. Criar novo item
    // ================================
    document.getElementById('btn-adicionar-item').addEventListener('click', () => {
        const nome = document.getElementById('novo_item').value.trim();

        if (!nome) {
            mostrarMensagem("Digite a pergunta do item.", "erro");
            return;
        }

        const formData = new FormData();
        formData.append('pergunta', nome);

        fetch('/ajax/auditoria_checklist_criar_item.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {

            if (data.erro) {
                mostrarMensagem(data.erro, "erro");
                return;
            }

            // Adiciona o novo item na lista
            const div = document.createElement('div');
            div.classList.add('setor-item');
            div.innerHTML = `
                <div class="setor-esquerda">
                    <input type="checkbox" class="check-item" value="${data.id}" id="item_${data.id}" checked>
                    <label for="item_${data.id}">${data.pergunta}</label>
                </div>

                <div class="setor-direita">
                    <button class="btn-edit" data-id="${data.id}">✏️</button>
                    <button class="btn-del" data-id="${data.id}">🗑️</button>
                </div>
            `;

            listaItens.appendChild(div);

            document.getElementById('novo_item').value = '';

            mostrarMensagem("Item criado com sucesso!", "sucesso");
        });
    });

    // ================================
    // 3. Editar item
    // ================================
    document.addEventListener('click', e => {
        if (e.target.classList.contains('btn-edit')) {

            const id = e.target.dataset.id;
            const label = e.target.closest('.setor-item').querySelector('label');
            const nomeAtual = label.textContent;

            // Preenche o modal
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_pergunta').value = nomeAtual;

            // Abre o modal
            document.getElementById('modalEditar').classList.remove('oculto');
        }
    });

    // Botão salvar edição
    document.getElementById('btnSalvarEdicao').addEventListener('click', () => {

        const id = document.getElementById('edit_id').value;
        const pergunta = document.getElementById('edit_pergunta').value.trim();

        if (!pergunta) {
            mostrarMensagem("Digite a pergunta.", "erro");
            return;
        }

        const formData = new FormData();
        formData.append('id', id);
        formData.append('pergunta', pergunta);

        fetch('/ajax/auditoria_checklist_editar_item.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(ret => {

            if (ret.erro) {
                mostrarMensagem(ret.erro, "erro");
                return;
            }

            // Atualiza o texto na lista
            const label = document.querySelector(`button[data-id="${id}"]`)
                            .closest('.setor-item')
                            .querySelector('label');

            label.textContent = pergunta;

            mostrarMensagem("Item atualizado!", "sucesso");

            // Fecha modal
            document.getElementById('modalEditar').classList.add('oculto');
        });
    });

    // Botão fechar modal
    document.getElementById('btnFecharModal').addEventListener('click', () => {
        document.getElementById('modalEditar').classList.add('oculto');
    });

    // ================================
    // 4. Excluir item
    // ================================
    document.addEventListener('click', e => {
        if (e.target.classList.contains('btn-del')) {

            if (!confirm("Tem certeza que deseja excluir este item?")) return;

            const id = e.target.dataset.id;

            const formData = new FormData();
            formData.append('id', id);

            fetch('/ajax/auditoria_checklist_excluir_item.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(msg => {
                e.target.closest('.setor-item').remove();
                mostrarMensagem("Item excluído!", "sucesso");
            });
        }
    });

    // ================================
    // 5. Salvar itens da loja
    // ================================
    document.getElementById('btn-salvar-itens').addEventListener('click', () => {
        const lojaId = lojaSelect.value;

        if (!lojaId) {
            mostrarMensagem("Selecione uma loja.", "erro");
            return;
        }

        const selecionados = [...document.querySelectorAll('.check-item:checked')].map(c => c.value);

        fetch('/ajax/auditoria_checklist_config_salvar.php', {
            method: 'POST',
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                loja_id: lojaId,
                ativos: selecionados
            })
        })
        .then(res => res.json())
        .then(ret => {
            if (ret.sucesso) {
                mostrarMensagem("Configurações salvas!", "sucesso");
            } else {
                mostrarMensagem("Erro ao salvar: " + ret.erro, "erro");
            }
        });
    });

});

// ================================
// 6. Mostrar mensagem PREMIUM
// ================================
function mostrarMensagem(msg, tipo = "sucesso") {

    const overlay = document.querySelector("#overlayMensagem");
    const box = document.querySelector("#mensagemTopo");

    const texto = box.querySelector("#textoMensagem");
    const icone = box.querySelector("#iconeMensagem");

    const icones = {
        sucesso: "✔️",
        erro: "❌",
        aviso: "⚠️"
    };

    icone.innerText = icones[tipo] || "ℹ️";
    texto.innerHTML = msg;

    box.classList.remove("erro", "sucesso", "aviso");
    box.classList.add(tipo);

    overlay.style.display = "block";
    box.style.display = "block";

    setTimeout(() => box.style.opacity = "1", 10);

    setTimeout(() => {
        box.style.opacity = "0";
        setTimeout(() => {
            overlay.style.display = "none";
            box.style.display = "none";
        }, 400);
    }, 4000);
}

// Fechar modal no X
document.getElementById('modalCloseX').addEventListener('click', () => {
    document.getElementById('modalEditar').classList.add('oculto');
});

// Fechar ao clicar fora
document.getElementById('modalEditar').addEventListener('click', e => {
    if (e.target.id === 'modalEditar') {
        document.getElementById('modalEditar').classList.add('oculto');
    }
});
