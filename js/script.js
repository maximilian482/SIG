// Abrir e fechar Menu lateral


  function toggleMenu() {
    const menu = document.getElementById('menuLateral');
    menu.classList.toggle('ativo');
  }

  // Fecha o menu ao clicar fora dele
  document.addEventListener('click', function (e) {
    const menu = document.getElementById('menuLateral');
    const toggle = document.querySelector('.menu-toggle');
    if (!menu.contains(e.target) && !toggle.contains(e.target)) {
      menu.classList.remove('ativo');
    }
  });

