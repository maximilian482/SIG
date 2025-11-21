document.addEventListener("DOMContentLoaded", function () {
  // Inicializa o editor da NOVA postagem
  const quillNovo = new Quill("#editor", {
    theme: "snow",
    modules: {
      toolbar: [
        ["bold", "italic", "underline"],
        ["link", "image"],
        [{ list: "ordered" }, { list: "bullet" }],
        ["clean"]
      ]
    }
  });

  // Handler para upload de imagens
  quillNovo.getModule("toolbar").addHandler("image", () => {
    const input = document.createElement("input");
    input.setAttribute("type", "file");
    input.setAttribute("accept", "image/*");
    input.click();

    input.onchange = () => {
      const file = input.files[0];
      if (file) {
        const formData = new FormData();
        formData.append("imagem", file);

        fetch("postagem/upload_imagem.php", {
          method: "POST",
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          if (data.url) {
            const range = quillNovo.getSelection();
            quillNovo.insertEmbed(range.index, "image", data.url);
          } else {
            alert("Erro ao enviar imagem: " + (data.erro || "desconhecido"));
          }
        })
        .catch(err => {
          console.error("Erro no upload:", err);
          alert("Falha na conexão ao enviar imagem.");
        });
      }
    };
  });

  // Preenche o campo oculto antes de enviar
  document.querySelector("form[action='postagem/postar.php']")
    .addEventListener("submit", function () {
      document.querySelector("#conteudo").value = quillNovo.root.innerHTML;
    });
});
