<?php
$idFuncionario = $_SESSION['id_funcionario'] ?? null;
$nomeUsuario = $_SESSION['usuario'] ?? 'Usuário';
$fotoPerfil = caminhoFotoPerfil($conn, $idFuncionario);
?>

<!-- includes/menu.php -->
<header class="menu-header">
  <div class="menu-toggle" onclick="toggleMenu()">☰</div>
</header>

<nav class="menu-lateral" id="menuLateral">
  <ul>    
    <li><a href="/index.php">🏠 Início</a></li>
    <li><a href="/modulos/acompanhar_chamados_publico.php">🛠️ Chamados</a></li>
    <li><a href="/modulos/pendencias.php">⏳ Pendências</a></li>
    <li><a href="/modulos/gestao.php">📊 Gestão</a></li>
    <li><a href="/perfil/perfil.php">👤 Meu Perfil</a></li>
    <li><a href="/modulos/avaliacoes.php">⭐ Avaliações</a></li>
    <li><a href="/modulos/comunidade.php">💬 Comunidade</a></li>
  </ul>
</nav>


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
</script>

