document.addEventListener('submit', function(e) {
  if (e.target.classList.contains('excluir-comentario-form')) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    // Descobre postagem_id a partir do modal aberto
    const postId = form.closest('.modal').id.replace('comentarios-', '');

    fetch(form.action, { method: 'POST', body: formData })
      .then(res => res.text())
      .then(html => {
        // Atualiza lista de comentários
        const lista = document.querySelector(`#comentarios-${postId} .lista-comentarios`);
        if (lista) lista.innerHTML = html;

        // Atualiza contador
        const novoTotal = lista.querySelectorAll('.comentario').length;
        const botao = document.querySelector(`button[onclick="abrirModal('comentarios-${postId}')"]`);
        if (botao) botao.innerHTML = `💬 Comentários (${novoTotal})`;
      })
      .catch(() => alert('Erro ao excluir comentário'));
  }
});
