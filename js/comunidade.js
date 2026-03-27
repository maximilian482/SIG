let currentLojaId = null;

// Reconhecer funcionário
function reconhecerFuncionario(id, tipo) {
  fetch('/perfil/reconhecer.php?funcionario_id=' + id + '&tipo=' + tipo, { credentials: 'same-origin' })
    .then(r => r.json())
    .then(data => {
      if (data.sucesso) {
        const botao = document.querySelector(
          `.parabens[onclick="reconhecerFuncionario(${id}, '${tipo}')"]`
        );
        if (!botao) return;

        const card = botao.closest('.card');
        const contadorSpan = card.querySelector('.contador');

        let numeroAtual = parseInt((contadorSpan.textContent.match(/\d+/) || [0])[0]) || 0;
        numeroAtual++;

        contadorSpan.textContent = tipo === 'aniversario'
          ? `🎉 ${numeroAtual} reconhecimentos de aniversário`
          : `👏 ${numeroAtual} reconhecimentos de tempo de empresa`;

        contadorSpan.classList.add('pulsar');
        setTimeout(() => contadorSpan.classList.remove('pulsar'), 800);

        botao.textContent = "✅ Reconhecido";
        botao.disabled = true;
        botao.style.backgroundColor = "#6c757d";
      } else {
        alert("Erro: " + (data.mensagem || "Falha ao reconhecer."));
      }
    })
    .catch(err => {
      console.error("Erro de comunicação com o servidor:", err);
      alert("Erro de comunicação com o servidor.");
    });
}


// Perfil público
function abrirPerfilPublico(id) {
  fetch('/perfil/publico.php?id=' + id)
    .then(res => {
      if (!res.ok) throw new Error("Erro ao carregar perfil");
      return res.text();
    })
    .then(html => {
      document.getElementById('perfilInfo').innerHTML = html;
      document.getElementById('perfilModal').style.display = 'block';
    })
    .catch(err => alert(err.message));
}

function fecharPerfilModal() {
  document.getElementById('perfilModal').style.display = 'none';
}

// Loja: detalhes e galeria
function abrirLojaDetalhes(id) {
  currentLojaId = id;

  fetch('../empresa/loja_detalhes.php?id=' + id, { credentials: 'same-origin' })
    .then(res => {
      if (!res.ok) throw new Error('Erro ao carregar detalhes da loja');
      return res.json();
    })
    .then(data => {
      // Nome da loja
      document.getElementById('lojaNome').textContent = data.nome || 'Loja';

      // Foto da fachada
      const fachadaImg = document.getElementById('lojaFachada');
      fachadaImg.src = data.foto_fachada || '/imagens/loja_padrao.jpg';

      // Galeria
      const galeria = document.getElementById('lojaGaleria');
      galeria.innerHTML = '';

      (data.imagens || []).forEach((src, idx) => {
        const img = document.createElement('img');
        img.src = src;
        img.className = 'thumb';
        img.alt = 'Imagem da loja';
        img.onclick = () => abrirCarousel(data.imagens, idx);
        galeria.appendChild(img);
      });

      // Fachada também abre o carrossel
      fachadaImg.onclick = () => abrirCarousel([data.foto_fachada, ...(data.imagens || [])], 0);

      // Abre modal
      document.getElementById('lojaModal').style.display = 'block';
    })
    .catch(err => {
      console.error(err);
      alert(err.message);
    });
}

function fecharLojaModal() {
  document.getElementById('lojaModal').style.display = 'none';
}

// Upload de foto da loja
function uploadFotoLoja(lojaId, file) {
  if (!lojaId || !file) return;

  const form = new FormData();
  form.append('loja_id', lojaId);
  form.append('foto', file);

  fetch('../empresa/loja_upload.php', {
    method: 'POST',
    body: form,
    credentials: 'same-origin'
  })
  .then(r => r.json())
  .then(data => {
    if (!data.sucesso) return alert(data.mensagem || 'Falha no upload');

    const img = document.createElement('img');
    img.src = data.caminho;
    img.className = 'thumb';
    img.alt = 'Imagem da loja';
    document.getElementById('lojaGaleria').prepend(img);
  })
  .catch(() => alert('Erro ao enviar a foto'));
}

// Carrossel
let imagensCarousel = [];
let indiceAtual = 0;

function abrirCarousel(imagens, indice) {
  imagensCarousel = imagens;
  indiceAtual = indice;
  atualizarImagem();
  document.getElementById('carouselModal').style.display = 'block';
}

function atualizarImagem() {
  document.getElementById('carouselImage').src = imagensCarousel[indiceAtual];
}

function mudarImagem(delta) {
  indiceAtual += delta;
  if (indiceAtual < 0) indiceAtual = imagensCarousel.length - 1;
  if (indiceAtual >= imagensCarousel.length) indiceAtual = 0;
  atualizarImagem();
}

function fecharCarousel() {
  document.getElementById('carouselModal').style.display = 'none';
}

// Atalhos de teclado
document.addEventListener('keydown', function(e) {
  if (document.getElementById('carouselModal').style.display === 'block') {
    if (e.key === 'Escape') fecharCarousel();
    if (e.key === 'ArrowLeft') mudarImagem(-1);
    if (e.key === 'ArrowRight') mudarImagem(1);
  }
});

// Excluir imagem atual
function excluirImagemAtual() {
  const imagemRemovida = imagensCarousel[indiceAtual];
  if (!confirm("Deseja realmente excluir esta foto?")) return;

  fetch('../empresa/loja_excluir_foto.php', {
    method: 'POST',
    body: JSON.stringify({ caminho: imagemRemovida, loja_id: currentLojaId }),
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin'
  })
  .then(r => r.json())
  .then(data => {
    if (!data.sucesso) return alert(data.mensagem || 'Falha ao excluir');

    // Remove da lista local
    imagensCarousel.splice(indiceAtual, 1);
    if (imagensCarousel.length === 0) {
      fecharCarousel();
    } else {
      if (indiceAtual >= imagensCarousel.length) indiceAtual = 0;
      atualizarImagem();
    }

    // Também remove da galeria
    const galeria = document.getElementById('lojaGaleria');
    [...galeria.querySelectorAll('img')].forEach(img => {
      if (img.src.includes(imagemRemovida)) img.remove();
    });
  })
  .catch(() => alert('Erro ao excluir a foto'));
}

// Controle das abas
function abrirAba(evt, nomeAba) {
  const conteudos = document.querySelectorAll('.tabcontent');
  conteudos.forEach(c => c.style.display = 'none');

  const botoes = document.querySelectorAll('.tablink');
  botoes.forEach(b => b.classList.remove('ativo'));

  const alvo = document.getElementById(nomeAba);
  if (alvo) alvo.style.display = 'block';

  if (evt && evt.currentTarget) {
    evt.currentTarget.classList.add('ativo');
  } else {
    const defaultBtn = document.getElementById('defaultOpen');
    if (defaultBtn) defaultBtn.classList.add('ativo');
  }
}

document.addEventListener('DOMContentLoaded', function() {
  const defaultBtn = document.getElementById('defaultOpen');
  if (defaultBtn) defaultBtn.click();
});

// Marcar interação como lida
// Marcar interação como lida
function marcarComoLido(id) {
  fetch('../perfil/marcar_lido.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
    body: new URLSearchParams({ tipo: 'interacao', id }),
    credentials: 'same-origin'
  })
  .then(r => r.json())
  .then(data => {
    if (data.sucesso) {
      const card = document.querySelector(`.card.interacao[data-id="${id}"]`);
      if (card) {
        const btn = card.querySelector('.btn-lido');
        if (btn) btn.remove(); // apenas remove o botão, sem adicionar nada
      }

      // Atualiza contador do botão da aba
      const btnInteracoes = document.getElementById('btn-interacoes');
      if (btnInteracoes) {
        let match = btnInteracoes.textContent.match(/\d+/);
        if (match) {
          let num = parseInt(match[0]);
          if (num > 0) {
            num--;
            btnInteracoes.textContent = num > 0
              ? `💬 Minhas Interações (${num})`
              : `💬 Minhas Interações`;
          }
        }
      }
    } else {
      alert(data.mensagem || 'Erro ao marcar como lido.');
    }
  })
  .catch(err => {
    console.error('Erro de comunicação:', err);
    alert('Erro de comunicação com o servidor.');
  });
}



// Excluir interação
function excluirInteracao(id) {
  if (!confirm('Deseja realmente excluir esta interação?')) return;

  fetch('/perfil/excluir_interacao.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
    body: new URLSearchParams({ id }),
    credentials: 'same-origin'
  })
  .then(r => r.json())
  .then(data => {
    if (data.sucesso) {
      const card = document.querySelector(`.card.interacao[data-id="${id}"]`);
      if (card) card.remove();

      // Atualiza contador do botão da aba
      const btnInteracoes = document.getElementById('btn-interacoes');
      if (btnInteracoes) {
        let match = btnInteracoes.textContent.match(/\d+/);
        if (match) {
          let num = parseInt(match[0]);
          if (num > 0) {
            num--;
            btnInteracoes.textContent = num > 0
              ? `💬 Minhas Interações (${num})`
              : `💬 Minhas Interações`;
          }
        }
      }
    } else {
      alert(data.mensagem || 'Erro ao excluir interação.');
    }
  })
  .catch(err => {
    console.error('Erro de comunicação:', err);
    alert('Erro de comunicação com o servidor.');
  });
}


// Upload de fachada Loja
function uploadFachadaLoja(lojaId, file) {
  if (!lojaId || !file) return;

  const form = new FormData();
  form.append('loja_id', lojaId);
  form.append('fachada', file);

  fetch('../empresa/loja_upload_fachada.php', {
    method: 'POST',
    body: form,
    credentials: 'same-origin'
  })
  .then(r => r.json())
  .then(data => {
    if (!data.sucesso) {
      alert(data.mensagem || 'Falha no upload da fachada');
      return;
    }

    // Atualiza imagem no modal
    const fachadaImg = document.getElementById('lojaFachada');
    if (fachadaImg) fachadaImg.src = data.caminho;

    // Atualiza imagem no card principal
    const card = document.querySelector(`#loja-card-${lojaId} img.fachada`);
    if (card) card.src = data.caminho;
  })
  .catch(() => alert('Erro ao enviar a fachada'));
}


// Marcar mensagem como lida
function marcarMensagemComoLida(id) {
  fetch('/perfil/marcar_mensagem_lida.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
    body: new URLSearchParams({ id }),
    credentials: 'same-origin'
  })
  .then(r => r.json())
  .then(data => {
    if (data.sucesso) {
      const card = document.querySelector(`.card.mensagem[data-id="${id}"]`);
      if (card) {
        const btn = card.querySelector('.btn-lido');
        if (btn) btn.remove();
        const span = document.createElement('span');
        span.className = 'lido';
        span.textContent = '✅ Já lida';
        card.appendChild(span);
      }

      // Atualiza contador da aba Mensagens
      const contador = document.querySelector('#btn-mensagens');
      if (contador) {
        let texto = contador.textContent;
        let numero = parseInt(texto.match(/\d+/));
        if (numero > 0) {
          contador.textContent = texto.replace(numero, numero - 1);
        }
      }
    }
  });
}

// Autocomplete destinatário mensagem
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('destinatario');
  const listaDiv = document.getElementById('autocomplete');

  input.addEventListener('input', async function() {
    const termo = this.value.trim();
    if (termo.length < 2) { listaDiv.style.display = 'none'; return; }

    try {
      const resp = await fetch('/perfil/buscar_funcionarios.php?q=' + encodeURIComponent(termo));
      const lista = await resp.json();

      listaDiv.innerHTML = '';
      if (lista.length > 0) {
        lista.forEach(f => {
          const item = document.createElement('div');
          item.className = 'autocomplete-item';
          item.textContent = f.nome;
          item.onclick = () => {
            input.value = f.nome;
            document.getElementById('funcionario_id').value = f.id;
            listaDiv.style.display = 'none';
          };
          listaDiv.appendChild(item);
        });
        listaDiv.style.display = 'block';
      } else {
        listaDiv.style.display = 'none';
      }
    } catch (e) {
      console.error('Erro na busca de funcionários', e);
    }
  });
});

