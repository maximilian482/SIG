// Reaproveita a mesma função usada em comunidade.php
function marcarComoLido(tipo, id) {
  fetch('../perfil/marcar_lido.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'tipo=' + encodeURIComponent(tipo) + '&id=' + encodeURIComponent(id)
  })
  .then(r => r.json())
  .then(data => {
    if (data.sucesso) {
      const card = document.querySelector(`[data-id="${id}"]`);
      if (card) {
        const btn = card.querySelector('.btn-lido');
        if (btn) btn.remove(); // apenas remove o botão, sem adicionar nada
      }

      // Atualiza contador
      const contador = document.querySelector('#contador-nao-lidas');
      if (contador) {
        let numero = parseInt(contador.textContent.match(/\d+/));
        if (numero > 0) {
          contador.textContent = `(${numero - 1} não lidas)`;
        }
      }
    }
  });
}


// Função para excluir mensagem
function excluirMensagem(id) {
  if (!confirm("Tem certeza que deseja excluir esta mensagem?")) return;

  fetch('../perfil/excluir_mensagem.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'id=' + encodeURIComponent(id),
    credentials: 'same-origin'
  })
  .then(r => r.json())
  .then(data => {
    if (data.sucesso) {
      const li = document.querySelector(`li[data-id="${id}"]`);
      if (li) {
        // Atualiza contador apenas se era não lida
        const contador = document.querySelector('#contador-nao-lidas');
        if (contador && data.eraNaoLida) {
          const match = contador.textContent.match(/\d+/);
          const atual = match ? parseInt(match[0], 10) : 0;
          const novo = Math.max(0, atual - 1);
          contador.textContent = `(${novo} não lidas)`;
        }
        li.remove();
      }
    } else {
      alert("Erro ao excluir: " + (data.mensagem || "desconhecido"));
    }
  })
  .catch(err => {
    alert("Erro de rede ao excluir. Tente novamente.");
    console.error(err);
  });
}
