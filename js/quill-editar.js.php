<?php foreach ($postagens as $post): ?>
<?php $postId = $post['id']; ?>

document.addEventListener("DOMContentLoaded", function () {

  const editorId = "editor-editar-<?= $postId ?>";
  const container = document.getElementById(editorId);

  if (!container) return;

  // Inicializa o Quill para este post
  const quill = new Quill("#" + editorId, {
    theme: "snow",
    modules: {
      toolbar: {
        container: [
          [{ header: [1, 2, false] }],
          ["bold", "italic", "underline"],
          ["link", "image"],
          [{ list: "ordered" }, { list: "bullet" }],
          [{ color: [] }, { background: [] }],
          [{ font: [] }],
          ["clean"]
        ],
        handlers: {
          image: function () {
            const input = document.createElement("input");
            input.setAttribute("type", "file");
            input.setAttribute("accept", "image/*");
            input.click();

            input.onchange = () => {
              const file = input.files[0];
              if (!file) return;

              const formData = new FormData();
              formData.append("imagem", file);

              fetch("postagem/upload_imagem.php", {
                method: "POST",
                body: formData
              })
              .then(r => r.json())
              .then(data => {
                if (data.url) {
                  const range = quill.getSelection(true);
                  quill.insertEmbed(range.index, "image", data.url, "user");
                } else {
                  alert("Erro ao enviar imagem: " + (data.erro || "desconhecido"));
                }
              })
              .catch(err => {
                console.error("Erro no upload:", err);
                alert("Falha ao enviar imagem.");
              });
            };
          }
        }
      }
    }
  });

  // Salvar conteúdo ao enviar o formulário de edição
  const form = document.querySelector("#form-editar-<?= $postId ?>");
  if (form) {
    form.addEventListener("submit", function () {
      const campoHidden = document.querySelector("#conteudo-editar-<?= $postId ?>");
      campoHidden.value = quill.root.innerHTML;
    });
  }

});
<?php endforeach; ?>
