document.addEventListener('DOMContentLoaded', () => {

    const lojaSelect = document.getElementById('loja_id');
    const setoresContainer = document.getElementById('setores-container');
    const listaSetores = document.getElementById('lista-setores');

    // ================================
    // 1. Carregar setores da loja
    // ================================
    lojaSelect.addEventListener('change', () => {
        const lojaId = lojaSelect.value;

        if (!lojaId) {
            setoresContainer.classList.add('oculto');
            listaSetores.innerHTML = '';
            return;
        }

        fetch(`/ajax/carregar_setores.php?loja_id=${lojaId}`)
            .then(res => res.text())
            .then(html => {
                listaSetores.innerHTML = html;
                setoresContainer.classList.remove('oculto');
            });
    });

    // ================================
    // 2. Criar novo setor
    // ================================
    document.getElementById('btn-adicionar-setor').addEventListener('click', () => {
        const nome = document.getElementById('novo_setor').value.trim();

        if (!nome) {
            mostrarMensagem("Digite um nome para o setor.", "erro");
            return;
        }

        const formData = new FormData();
        formData.append('nome', nome);

        fetch('/ajax/criar_setor.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {

            if (data.erro) {
                mostrarMensagem(data.erro, "erro");
                return;
            }

            // Adiciona o novo setor na lista
            const div = document.createElement('div');
            div.classList.add('item-setor');
            div.innerHTML = `
                <input type="checkbox" class="check-setor" value="${data.id}" id="setor_${data.id}" checked>
                <label for="setor_${data.id}">${data.nome}</label>

                <button class="btn-editar" data-id="${data.id}">✏️</button>
                <button class="btn-excluir" data-id="${data.id}">🗑️</button>
            `;

            listaSetores.appendChild(div);

            document.getElementById('novo_setor').value = '';

            mostrarMensagem("Setor criado com sucesso!", "sucesso");
        });
    });

    // ================================
    // 3. Editar setor
    // ================================
    document.addEventListener('click', e => {
        if (e.target.classList.contains('btn-editar')) {
            const id = e.target.dataset.id;
            const label = e.target.parentElement.querySelector('label');
            const nomeAtual = label.textContent;

            const novoNome = prompt("Editar nome do setor:", nomeAtual);

            if (!novoNome || novoNome.trim() === '') return;

            const formData = new FormData();
            formData.append('id', id);
            formData.append('nome', novoNome);

            fetch('/ajax/editar_setor.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(msg => {
                label.textContent = novoNome;
                mostrarMensagem("Setor atualizado!", "sucesso");
            });
        }
    });

    // ================================
    // 4. Excluir setor
    // ================================
    document.addEventListener('click', e => {
        if (e.target.classList.contains('btn-excluir')) {

            if (!confirm("Tem certeza que deseja excluir este setor?")) return;

            const id = e.target.dataset.id;

            const formData = new FormData();
            formData.append('id', id);

            fetch('/ajax/excluir_setor.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(msg => {
                e.target.parentElement.remove();
                mostrarMensagem("Setor excluído!", "sucesso");
            });
        }
    });

    // ================================
    // 5. Salvar setores da loja
    // ================================
    document.getElementById('btn-salvar-setores').addEventListener('click', () => {
        const lojaId = lojaSelect.value;

        if (!lojaId) {
            mostrarMensagem("Selecione uma loja.", "erro");
            return;
        }

        const selecionados = [...document.querySelectorAll('.check-setor:checked')].map(c => c.value);

        const formData = new FormData();
        formData.append('loja_id', lojaId);
        selecionados.forEach(id => formData.append('setores[]', id));

        fetch('/ajax/salvar_setores.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(msg => {
            mostrarMensagem("Configurações salvas!", "sucesso");
        });
    });

});
