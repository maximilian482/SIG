// Envio de comentário via AJAX
document.querySelectorAll('.comentario-form').forEach(form => {
  form.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(form);
    const postId = form.querySelector('[name="postagem_id"]').value;

    fetch(form.action, {
      method: 'POST',
      body: formData
    })
    .then(res => res.text())
    .then(html => {
      // Atualiza apenas a lista de comentários
      const lista = document.querySelector(`#comentarios-${postId} .lista-comentarios`);
      if (lista) {
        lista.innerHTML = html;
      }

      // Limpa o campo de texto
      form.querySelector('[name="texto"]').value = '';

      // Atualiza contador de comentários
      const novoTotal = lista.querySelectorAll('.comentario').length;
      const botao = document.querySelector(`button[onclick="abrirModal('comentarios-${postId}')"]`);
      if (botao) {
        botao.innerHTML = `💬 Comentários (${novoTotal})`;
      }
    })
    .catch(() => alert('Erro ao enviar comentário'));
  });
});

// Fecha o modal ao clicar fora
document.addEventListener('click', function(e) {
  document.querySelectorAll('.modal').forEach(modal => {
    const content = modal.querySelector('.modal-content');
    const isOpen = getComputedStyle(modal).display !== 'none';

    const clicouFora = isOpen &&
      !content.contains(e.target) &&
      !e.target.closest('[onclick^="abrirModal("]');

    if (clicouFora) {
      modal.style.display = 'none';
    }
  });
});
