document.addEventListener("DOMContentLoaded", function () {

    // ===============================
    // SUGESTÃO DE SETOR AO ESCOLHER CARGO
    // ===============================
    const cargoSelect = document.getElementById('cargo_id');
    const setorSelect = document.getElementById('id_setor');

    if (cargoSelect && setorSelect) {
        cargoSelect.addEventListener('change', function () {
            const cargoId = parseInt(this.value);
            const setorId = mapaCargoSetor[cargoId] ?? setorGeral;

            setorSelect.value = setorId;

            setorSelect.classList.add('setor-sugerido');
            setTimeout(() => setorSelect.classList.remove('setor-sugerido'), 1500);
        });
    }

    // ===============================
    // MODAL: CARGO
    // ===============================
    window.abrirModalCargo = function () {
        abrirModal('modalCargo'); // usa sistema global
    };

    window.fecharModalCargo = function () {
        fecharModal('modalCargo'); // usa sistema global
    };

    // ===============================
    // MODAL: SETOR
    // ===============================
    window.abrirModalSetor = function () {
        abrirModal('modalSetor'); // usa sistema global
    };

    window.fecharModalSetor = function () {
        fecharModal('modalSetor'); // usa sistema global
    };

    // ===============================
    // SALVAR NOVO CARGO
    // ===============================
    window.salvarCargo = function () {
        const nome = document.getElementById('novoCargo').value.trim();
        const descricao = document.getElementById('descricaoCargo').value.trim();

        if (!nome) {
            mostrarMensagem("Digite um nome para o cargo.", "aviso");
            return;
        }

        fetch('funcionarios_novo_cargo.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'nome=' + encodeURIComponent(nome) +
                  '&descricao=' + encodeURIComponent(descricao)
        })
        .then(r => r.json())
        .then(data => {
            if (data.sucesso) {
                const select = document.getElementById('cargo_id');
                const option = new Option(data.nome, data.id, true, true);
                select.add(option);

                mostrarMensagem("Cargo criado com sucesso!", "sucesso");
                fecharModalCargo();
            } else {
                mostrarMensagem(data.erro, "erro");
            }
        })
        .catch(() => mostrarMensagem("Erro ao criar cargo.", "erro"));
    };

    // ===============================
    // SALVAR NOVO SETOR
    // ===============================
    window.salvarSetor = function () {
        const nome = document.getElementById('novoSetor').value.trim();

        if (!nome) {
            mostrarMensagem("Digite um nome para o setor.", "aviso");
            return;
        }

        fetch('funcionarios_salvar_setor.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'nome=' + encodeURIComponent(nome)
        })
        .then(r => r.json())
        .then(data => {
            if (data.sucesso) {
                const select = document.getElementById('id_setor');
                const option = new Option(data.nome, data.id, true, true);
                select.add(option);

                mostrarMensagem("Setor criado com sucesso!", "sucesso");
                fecharModalSetor();
            } else {
                mostrarMensagem(data.erro, "erro");
            }
        })
        .catch(() => mostrarMensagem("Erro ao criar setor.", "erro"));
    };

});
