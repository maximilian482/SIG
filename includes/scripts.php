<!-- includes/scripts.php -->
<script>
  function toggleMenu() {
    const menu = document.getElementById('menuLateral');
    menu.classList.toggle('ativo');
  }

  function toggleMenuPerfil() {
    const menu = document.getElementById('menuPerfil');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
  }

  document.addEventListener('click', function (e) {
    const menu = document.getElementById('menuLateral');
    const toggle = document.querySelector('.menu-toggle');
    if (!menu.contains(e.target) && !toggle.contains(e.target)) {
      menu.classList.remove('ativo');
    }

    const perfilMenu = document.getElementById('menuPerfil');
    const perfilFoto = document.querySelector('.perfil-foto');
    if (!perfilMenu.contains(e.target) && !perfilFoto.contains(e.target)) {
      perfilMenu.style.display = 'none';
    }
  });


  document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".post img").forEach(function (img) {
    img.onload = function () {
      if (img.naturalWidth > 2000 || img.naturalHeight > 2000) {
        const aviso = document.createElement("div");
        aviso.textContent = "⚠️ Imagem oculta por exceder tamanho permitido.";
        aviso.style.color = "red";
        aviso.style.fontWeight = "bold";
        img.replaceWith(aviso);
      }
    };
    img.onerror = function () {
      img.replaceWith(document.createTextNode("⚠️ Erro ao carregar imagem."));
    };
  });
});

document.querySelector("form").addEventListener("submit", function (e) {
  const editorContent = document.querySelector("#editor").innerHTML.trim();
  const hiddenInput = document.querySelector("#conteudo");

  // Remove tags vazias e espaços
  const textoLimpo = editorContent.replace(/<[^>]*>/g, '').trim();

  if (textoLimpo === "") {
    alert("⚠️ A mensagem está vazia. Escreva algo antes de publicar.");
    e.preventDefault();
    return false;
  }

  hiddenInput.value = editorContent;
});
</script>
