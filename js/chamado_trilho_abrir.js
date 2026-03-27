function adicionarItem() {
    const container = document.getElementById('listaItens');

    const div = document.createElement('div');
    div.className = 'item-trilho';
    div.style.marginBottom = '10px';

    div.innerHTML = `
        <input type="text" name="item_codigo[]" placeholder="Código Interno" required>
        <input type="text" name="item_descricao[]" placeholder="Descrição do item">
        <input type="number" name="item_quantidade[]" placeholder="Qtd" min="1" value="1" required>
        <button type="button" onclick="this.parentNode.remove()">❌</button>
    `;

    container.appendChild(div);
}
