document.querySelectorAll(".loja-menu button").forEach(btn => {
    btn.addEventListener("click", () => {

        // remover ativo do menu
        document.querySelectorAll(".loja-menu button")
            .forEach(b => b.classList.remove("ativa"));

        btn.classList.add("ativa");

        const aba = btn.dataset.aba;

        // esconder todas as abas
        document.querySelectorAll(".conteudo-aba")
            .forEach(div => div.classList.remove("ativo"));

        // mostrar aba selecionada
        document.getElementById(aba).classList.add("ativo");
    });
});


function abrirModalCertificado() {
    document.getElementById('modalCertificado').style.display = 'flex';
}
function fecharModalCertificado() {
    document.getElementById('modalCertificado').style.display = 'none';
}

function toggleSenhaCert() {
    const campo = document.getElementById('senhaCert');
    if (!campo) return;

    campo.type = campo.type === 'password' ? 'text' : 'password';
}


function excluirDispositivo(id) {
    if (!confirm("Tem certeza que deseja excluir este dispositivo?")) return;

    window.location = "loja_dispositivo_excluir.php?id=" + id + "&loja=<?= $lojaId ?>";
}

