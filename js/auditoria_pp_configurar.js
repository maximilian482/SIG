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

        fetch(`/ajax/auditoria_pp_carregar_itens.php?loja_id=${lojaId}`)
            .then(res => res.text())
            .then(html => {
                listaItens.innerHTML = html;
                itensContainer.classList.remove('oculto');
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

        fetch('/ajax/auditoria_pp_criar_item.php', {
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

            const novoNome = prompt("Editar item:", nomeAtual);

            if (!novoNome || novoNome.trim() === '') return;

            const formData = new FormData();
            formData.append('id', id);
            formData.append('pergunta', novoNome);

            fetch('/ajax/auditoria_pp_editar_item.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(msg => {
                label.textContent = novoNome;
                mostrarMensagem("Item atualizado!", "sucesso");
            });
        }
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

            fetch('/ajax/auditoria_pp_excluir_item.php', {
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

        const formData = new FormData();
        formData.append('loja_id', lojaId);
        selecionados.forEach(id => formData.append('itens[]', id));

        fetch('/ajax/auditoria_pp_salvar_item.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(msg => {
            mostrarMensagem("Configurações salvas!", "sucesso");
        });
    });

});

  // ================================
    // 6. Mostrar mensagem
    // ================================

function mostrarMensagem(msg, tipo = "sucesso") {

    const overlay = document.querySelectorAll("#overlayMensagem")[1];
    const box = document.querySelectorAll("#mensagemTopo")[1];

    const texto = box.querySelector("#textoMensagem");
    const icone = box.querySelector("#iconeMensagem");

    const icones = {
        sucesso: "✔️",
        erro: "❌",
        aviso: "⚠️"
    };

    icone.innerText = icones[tipo] || "ℹ️";
    texto.innerHTML = msg;

    if (tipo === "sucesso") {
        box.style.background = "var(--verde-palmeiras-claro)";
        box.style.color = "white";
    } else if (tipo === "erro") {
        box.style.background = "var(--erro-bg)";
        box.style.color = "var(--erro-texto)";
    } else if (tipo === "aviso") {
        box.style.background = "var(--warning-bg)";
        box.style.color = "var(--warning-texto)";
    }

    overlay.style.display = "block";
    box.style.display = "block";

    setTimeout(() => box.style.opacity = "1", 10);

    setTimeout(() => {
        box.style.opacity = "0";
        setTimeout(() => {
            overlay.style.display = "none";
            box.style.display = "none";
        }, 400);
    }, 5000);
}
