<?php foreach ($postagens as $post): ?>
  <?php $postId = $post['id']; ?>
  document.addEventListener("DOMContentLoaded", function () {
    const editorContainerId = 'editor-editar-<?= $postId ?>';
    const container = document.getElementById(editorContainerId);
    if (container) {
      window['quillEditar<?= $postId ?>'] = new Quill('#' + editorContainerId, {
        theme: 'snow',
        modules: {
          toolbar: [
            [{ header: [1, 2, false] }],
            ['bold', 'italic', 'underline'],
            ['link', 'image'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            [{ color: [] }, { background: [] }],
            [{ font: [] }],
            ['clean']
          ]
        }
      });
    }
  });
<?php endforeach; ?>
