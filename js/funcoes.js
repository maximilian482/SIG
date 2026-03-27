// Alterna visibilidade do menu de perfil
function toggleMenu() {
  const menu = document.getElementById("menuPerfil");
  if (menu) {
    menu.style.display = (menu.style.display === "block") ? "none" : "block";
  }
}

// Fecha o menu se clicar fora
document.addEventListener("click", function (e) {
  const menu = document.getElementById("menuPerfil");
  const perfilIcone = document.querySelector("img[alt='Perfil']");
  if (menu && perfilIcone && !e.target.closest("#menuPerfil") && !e.target.closest("img[alt='Perfil']")) {
    menu.style.display = "none";
  }
});

// Confirmação para ações sensíveis
function confirmarAcao(mensagem, callback) {
  if (confirm(mensagem)) {
    callback();
  }
}

// Exemplo de uso:
// confirmarAcao("Tem certeza que deseja sair?", () => window.location.href = "logout.php");

// Mostrar/ocultar elementos com animação simples
function toggleElemento(id) {
  const el = document.getElementById(id);
  if (el) {
    el.style.display = (el.style.display === "none" || el.style.display === "") ? "block" : "none";
  }
}

// Exibir mensagem temporária
function mostrarMensagem(id, tempo = 3000) {
  const el = document.getElementById(id);
  if (el) {
    el.style.display = "block";
    setTimeout(() => {
      el.style.display = "none";
    }, tempo);
  }
}

