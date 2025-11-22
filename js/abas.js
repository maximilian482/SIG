function abrirAba(evt, nomeAba) {
  var i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }
  tablinks = document.getElementsByClassName("tablink");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }
  document.getElementById(nomeAba).style.display = "block";
  if (evt) evt.currentTarget.className += " active";
}

// Abre a aba correta ao carregar
document.addEventListener("DOMContentLoaded", function() {
  const params = new URLSearchParams(window.location.search);
  const aba = params.get("aba") || "aniversario"; // padrão
  const botao = document.querySelector(`.tablink[onclick*="${aba}"]`);
  if (botao) {
    botao.click();
  }
});
