// ===============================
// Modal: Novo Item
// ===============================
function abrirModalNovoItem() {
    document.getElementById('modalNovoItem').style.display = 'block';
}

function fecharModalNovoItem() {
    document.getElementById('modalNovoItem').style.display = 'none';
}

function salvarNovoItem() {
    const nome = document.getElementById('novoItemNome').value.trim();
    const valor = document.getElementById('novoItemValor').value.trim();

    if (!nome) {
        alert("Digite o nome do item.");
        return;
    }

    const select = document.getElementById('nome');
    const option = document.createElement("option");
    option.value = nome;
    option.textContent = nome;
    select.appendChild(option);
    select.value = nome;

    if (valor) {
        document.getElementById('valor').value = valor;
    }

    fecharModalNovoItem();
}


// ===============================
// Modal: Novo Setor
// ===============================
function abrirModalNovoSetor() {
    document.getElementById('modalNovoSetor').style.display = 'block';
}

function fecharModalNovoSetor() {
    document.getElementById('modalNovoSetor').style.display = 'none';
}

function salvarNovoSetor() {
    const nome = document.getElementById('novoSetorNome').value.trim();

    if (!nome) {
        alert("Digite o nome do setor.");
        return;
    }

    const select = document.getElementById('setor');
    const option = document.createElement("option");
    option.value = nome;
    option.textContent = nome;
    select.appendChild(option);
    select.value = nome;

    fecharModalNovoSetor();
}


// ===============================
// Atualizar valor sugerido
// ===============================
function atualizarValor() {
    const nome = document.getElementById('nome').value;
    const valores = window.valoresPadrao || {};
    const campoValor = document.getElementById('valor');

    campoValor.value = valores[nome] || '';
}
