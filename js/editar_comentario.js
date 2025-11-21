// Envio de edição de comentário via AJAX
document.querySelectorAll('.editar-comentario-form').forEach(form => {
  form.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(form);
    const postId = form.querySelector('[name="postagem_id"]').value;
    const comentarioId = form.querySelector('[name="comentario_id"]').value;

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

      // Fecha o modal de edição
      const modal = document.getElementById(`editar-comentario-${comentarioId}`);
      if (modal) {
        modal.style.display = 'none';
      }

      // Atualiza contador de comentários
      const novoTotal = lista.querySelectorAll('.comentario').length;
      const botao = document.querySelector(`button[onclick="abrirModal('comentarios-${postId}')"]`);
      if (botao) {
        botao.innerHTML = `💬 Comentários (${novoTotal})`;
      }
    })
    .catch(() => alert('Erro ao editar comentário'));
  });
});
