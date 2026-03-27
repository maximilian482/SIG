// js/avaliacao.js
document.addEventListener('click', function(e) {
  const btn = e.target.closest('.btn-nota');
  if (!btn) return;

  const form = btn.closest('.form-avaliacao');
  if (!form) return;

  const postId = form.querySelector('[name="postagem_id"]').value;
  const notaHidden = form.querySelector('[name="nota"]');
  notaHidden.value = btn.dataset.nota; // seta a nota escolhida

  const formData = new FormData(form);

  fetch(form.action, { method: 'POST', body: formData })
    .then(res => res.text())
    .then(html => {
      // Atualiza lista de avaliações
      const lista = document.querySelector(`#avaliacoes-${postId} .lista-avaliacoes`);
      if (lista) lista.innerHTML = html;

      // Atualiza botão do feed (total e média)
      const items = lista.querySelectorAll('.avaliacao-item');
      const total = items.length;
      const mapa = { '😡':1,'👎':2,'😐':3,'👍':4,'😍':5 };
      let soma = 0;
      items.forEach(item => {
        const emoji = item.querySelector('span')?.textContent || '';
        soma += mapa[emoji] || 0;
      });
      const media = total > 0 ? (soma / total).toFixed(1) : '-';

      const botao = document.getElementById(`btn-avaliacoes-${postId}`);
      if (botao) botao.innerHTML = `⭐ Avaliações (${total}) Média: ${media}`;
    })
    .catch(() => alert('Erro ao enviar avaliação'));
});
