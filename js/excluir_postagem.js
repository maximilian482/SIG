document.addEventListener("submit", function(e) {
  if (e.target.classList.contains("excluir-postagem-form")) {
    e.preventDefault(); // impede o navegador de abrir excluir_postagem.php

    const form = e.target;
    const formData = new FormData(form);

    if (!confirm("Tem certeza que deseja excluir esta postagem?")) {
      return;
    }

    fetch(form.action, {
      method: "POST",
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.sucesso) {
        // Remove o card da postagem
        const card = form.closest(".post");
        if (card) card.remove();
      } else {
        alert("Erro ao excluir: " + (data.erro || "tente novamente"));
      }
    })
    .catch(err => {
      console.error("❌ Erro na requisição:", err);
      alert("Erro de conexão ao excluir postagem.");
    });
  }
});
