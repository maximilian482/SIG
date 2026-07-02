function carregarGestao(pagina = 1) {

    const status = document.getElementById('filtroStatus').value;
    const loja   = document.getElementById('filtroLoja').value;
    const busca  = document.getElementById('filtroBusca').value.trim();

    fetch("/ajax/compras_externas_gestao_listar.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ pagina, status, loja, busca })
    })
    .then(r => r.json())
    .then(ret => {

        if (!ret.sucesso) {
            alert(ret.erro);
            return;
        }

        const tbody = document.getElementById('listaGestao');
        tbody.innerHTML = "";

        if (ret.dados.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        Nenhuma solicitação encontrada.
                    </td>
                </tr>
            `;
        } else {
            ret.dados.forEach(c => {

                const statusClass = {
                    'aberto': 'badge badge-status-aberto',
                    'em_compra': 'badge badge-status-em-compra',
                    'aguardando_documentos': 'badge badge-status-aguardando',
                    'concluido': 'badge badge-status-concluido'
                }[c.status] || 'badge bg-secondary';

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>#${c.id}</td>
                    <td>${c.loja}</td>
                    <td>${c.produto}</td>
                    <td>${c.solicitante}</td>
                    <td><span class="${statusClass}">${c.status.toUpperCase()}</span></td>
                    <td class="text-center">

                        ${c.status !== 'concluido' ? `
                            <a href="compras_externas_gestao_finalizar.php?id=${c.id}" 
                               class="btn btn-sm btn-success btn-icon" title="Finalizar">
                                ✅
                            </a>
                        ` : ''}

                        <a href="compras_externas_gestao_detalhes.php?id=${c.id}" 
                           class="btn btn-sm btn-outline-primary btn-icon" title="Detalhes">
                            🔍
                        </a>

                        ${c.status !== 'concluido' ? `
                            <button class="btn btn-sm btn-outline-danger btn-icon"
                                    onclick="excluirSolicitacao(${c.id})" title="Excluir">
                                🗑️
                            </button>
                        ` : ''}

                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Info total
        document.getElementById('infoTotal').textContent =
            `Total: ${ret.total} registro(s) • Página ${ret.pagina} de ${ret.paginas}`;

        // Paginação
        const pag = document.getElementById('paginacaoGestao');
        pag.innerHTML = "";

        const criar = (label, page, disabled = false, active = false) => {
            const li = document.createElement('li');
            li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
            const a = document.createElement('button');
            a.className = 'page-link';
            a.textContent = label;
            if (!disabled && !active) a.onclick = () => carregarGestao(page);
            li.appendChild(a);
            return li;
        };

        pag.appendChild(criar('«', 1, ret.pagina === 1));
        pag.appendChild(criar('‹', ret.pagina - 1, ret.pagina === 1));

        for (let p = 1; p <= ret.paginas; p++) {
            pag.appendChild(criar(p, p, false, p === ret.pagina));
        }

        pag.appendChild(criar('›', ret.pagina + 1, ret.pagina === ret.paginas));
        pag.appendChild(criar('»', ret.paginas, ret.pagina === ret.paginas));

    });
}

// Eventos
document.getElementById('btnFiltrar').addEventListener('click', () => carregarGestao(1));
document.getElementById('filtroBusca').addEventListener('keyup', e => {
    if (e.key === 'Enter') carregarGestao(1);
});

// Carregar inicial
carregarGestao(1);
