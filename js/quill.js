document.addEventListener("DOMContentLoaded", function () {

  // Inicializa o editor da NOVA postagem
  const quillNovo = new Quill("#editor", {
    theme: "snow",
    modules: {
      toolbar: {
        container: [
          ["bold", "italic", "underline"],
          ["link", "image"],
          [{ list: "ordered" }, { list: "bullet" }],
          ["clean"]
        ],
        handlers: {
          image: imageHandler
        }
      }
    }
  });

  // Handler para upload de imagens
  function imageHandler() {
    const input = document.createElement("input");
    input.setAttribute("type", "file");
    input.setAttribute("accept", "image/*");
    input.click();

    input.onchange = () => {
      const file = input.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append("imagem", file); // <-- compatível com seu upload_imagem.php

      fetch("postagem/upload_imagem.php", {
        method: "POST",
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.url) {
          const range = quillNovo.getSelection(true); // garante posição válida
          quillNovo.insertEmbed(range.index, "image", data.url, "user");
        } else {
          alert("Erro ao enviar imagem: " + (data.erro || "desconhecido"));
        }
      })
      .catch(err => {
        console.error("Erro no upload:", err);
        alert("Falha na conexão ao enviar imagem.");
      });
    };
  }

  // Preenche o campo oculto antes de enviar
  const form = document.querySelector("form[action='postagem/postar.php']");
  if (form) {
    form.addEventListener("submit", function () {
      document.querySelector("#conteudo").value = quillNovo.root.innerHTML;
    });
  }
});
