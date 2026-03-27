(function(){
    const tipoEl = document.getElementById('responsavel_tipo');
    const sel = document.getElementById('responsavel_id');
    const form = document.getElementById('formTarefa');
    const btn = document.getElementById('btnSubmit');

    function tipoParaParametro(tipo) {
        return tipo || '';
    }

    function carregarResponsaveis(tipo, selectedId) {
        sel.innerHTML = '<option>Carregando...</option>';
        const tipoParam = tipoParaParametro(tipo);
        if (!tipoParam) {
            sel.innerHTML = '<option value="">Selecione</option>';
            return;
        }
        fetch('ajax_responsaveis.php?tipo=' + encodeURIComponent(tipoParam))
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(data => {
                sel.innerHTML = '<option value="">Selecione</option>';
                if (!Array.isArray(data) || data.length === 0) {
                    sel.innerHTML = '<option value="">Nenhum responsável encontrado</option>';
                    return;
                }
                data.forEach(i => {
                    const opt = document.createElement('option');
                    opt.value = String(i.id);
                    opt.textContent = i.nome;
                    sel.appendChild(opt);
                });
                if (selectedId) sel.value = String(selectedId);
            })
            .catch(err => {
                console.error('Erro ao carregar responsáveis:', err);
                sel.innerHTML = '<option value="">Erro ao carregar</option>';
            });
    }

    const initialTipo = tipoEl ? tipoEl.value : '';
    const initialId = sel ? sel.value : '';

    if (initialTipo) carregarResponsaveis(initialTipo, initialId);

    if (tipoEl) {
        tipoEl.addEventListener('change', function () {
            carregarResponsaveis(this.value, null);
        });
    }

    if (form && btn) {
        form.addEventListener('submit', function(e){
            if (btn.disabled) {
                e.preventDefault();
                return;
            }
            btn.disabled = true;
            btn.textContent = 'Enviando...';
        });
    }
})();
