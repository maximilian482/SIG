<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Teste Autocomplete</title>
  <style>
    .autocomplete-list {
      position:absolute;
      background:#fff;
      border:1px solid #ccc;
      border-radius:6px;
      max-height:150px;
      overflow-y:auto;
      width:300px;
      z-index:1000;
    }
    .autocomplete-item {
      padding:8px;
      cursor:pointer;
    }
    .autocomplete-item:hover {
      background:#f0f0f0;
    }
    .wrapper { position:relative; max-width:300px; margin:40px auto; }
  </style>
</head>
<body>
<div class="wrapper">
  <label for="destinatario">Destinatário:</label>
  <input type="text" id="destinatario" placeholder="Digite o nome do funcionário..." autocomplete="off" style="width:100%; padding:8px;">
  <input type="hidden" name="funcionario_id" id="funcionario_id">
  <div id="autocomplete" class="autocomplete-list" style="display:none;"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('destinatario');
  const listaDiv = document.getElementById('autocomplete');
  const hiddenId = document.getElementById('funcionario_id');

  input.addEventListener('input', async function() {
    const termo = this.value.trim();
    if (termo.length < 2) { listaDiv.style.display = 'none'; return; }

    try {
      // Como este arquivo está dentro de /perfil/, usamos caminho relativo:
      const resp = await fetch('./buscar_funcionarios.php?q=' + encodeURIComponent(termo));
      const lista = await resp.json();

      listaDiv.innerHTML = '';
      if (Array.isArray(lista) && lista.length > 0) {
        lista.forEach(f => {
          const item = document.createElement('div');
          item.className = 'autocomplete-item';
          item.textContent = f.nome;
          item.onclick = () => {
            input.value = f.nome;
            hiddenId.value = f.id;
            listaDiv.style.display = 'none';
          };
          listaDiv.appendChild(item);
        });
        listaDiv.style.display = 'block';
      } else {
        listaDiv.style.display = 'none';
      }
    } catch (e) {
      console.error('Erro na busca de funcionários:', e);
      listaDiv.style.display = 'none';
    }
  });

  // Fecha a lista ao clicar fora
  document.addEventListener('click', (evt) => {
    if (!listaDiv.contains(evt.target) && evt.target !== input) {
      listaDiv.style.display = 'none';
    }
  });
});
</script>
</body>
</html>
