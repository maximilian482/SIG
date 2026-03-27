(function(){
    function tipoParaParametro(tipo) {
        return tipo || '';
    }

    const tipoAtual = document.getElementById('responsavel_tipo').value;
    const idAtual = document.getElementById('responsavel_id').value;

    const selTipo = document.getElementById('responsavel_tipo');
    const selResp = document.getElementById('responsavel_id');

    function carregarResponsaveis(tipo, selecionado) {
        selResp.innerHTML = '<option value="">Carregando...</option>';
        const tipoParam = tipoParaParametro(tipo);

        if (!tipoParam) {
            selResp.innerHTML = '<option value="">Selecione</option>';
            return;
        }

        fetch('ajax_responsaveis.php?tipo=' + encodeURIComponent(tipoParam))
            .then(r => r.json())
            .then(data => {
                selResp.innerHTML = '<option value="">Selecione</option>';

                if (!Array.isArray(data) || data.length === 0) {
                    selResp.innerHTML = '<option value="">Nenhum responsável encontrado</option>';
                    return;
                }

                data.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.nome;
                    if (String(item.id) === String(selecionado)) opt.selected = true;
                    selResp.appendChild(opt);
                });
            })
            .catch(() => {
                selResp.innerHTML = '<option value="">Erro ao carregar</option>';
            });
    }

    if (tipoAtual) carregarResponsaveis(tipoAtual, idAtual);

    selTipo.addEventListener('change', function () {
        carregarResponsaveis(this.value, 0);
    });

    document.getElementById('formEditar').addEventListener('submit', function(e){
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Salvando...';
    });
})();
