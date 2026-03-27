// FUNÇÕES DO MODAL — SEM DOMContentLoaded
window.abrirModalDetalhesChamadoAdmin = function(id) {
    const modal    = document.getElementById('modalDetalhesChamado');
    const conteudo = document.getElementById('conteudoDetalhesChamado');

    conteudo.innerHTML = 'Carregando...';

    fetch('chamados_detalhes.php?id=' + id)
        .then(r => r.text())
        .then(html => {
            conteudo.innerHTML = html;
            modal.style.display = 'block';
        })
        .catch(() => {
            conteudo.innerHTML = 'Erro ao carregar detalhes.';
        });
};

window.fecharModalDetalhesChamado = function() {
    document.getElementById('modalDetalhesChamado').style.display = 'none';
};

// FECHAR AO CLICAR FORA
window.addEventListener('click', function(event) {
    const modal = document.getElementById('modalDetalhesChamado');
    if (event.target === modal) modal.style.display = 'none';
});

// FECHAR COM ESC
window.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
        const modal = document.getElementById('modalDetalhesChamado');
        modal.style.display = 'none';
    }
});
