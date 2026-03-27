<?php
if ($post['autor_id'] == $idFuncionario):
  $conteudoOriginal = $post['conteudo'];
  $textoLimpo = preg_replace('/<img[^>]+>/i', '', $conteudoOriginal);
?>
<div id="editar-<?= $postId ?>" class="modal">
  <div class="modal-content">
    <span onclick="fecharModal('editar-<?= $postId ?>')" class="close">&times;</span>
    <h3>✏️ Editar Postagem</h3>
    <form method="POST" action="postagem/editar_postagem.php" onsubmit="document.getElementById('conteudo-editar-<?= $postId ?>').value = quillEditar<?= $postId ?>.root.innerHTML;">
      <input type="hidden" name="postagem_id" value="<?= $postId ?>">
      <input type="hidden" name="conteudo" id="conteudo-editar-<?= $postId ?>">
      <div id="editor-editar-<?= $postId ?>" style="height:150px;">
        <?= $post['conteudo'] ?>
      </div>
      <button type="submit" style="margin-top:10px;">Salvar</button>
    </form>
  </div>
</div>
<?php endif; ?>

<div id="avaliacoes-<?= $postId ?>" class="modal">
  <div class="modal-content">
    <span onclick="fecharModal('avaliacoes-<?= $postId ?>')" class="close">&times;</span>
    <h3>⭐ Avaliações</h3>
    <p><strong>Faça sua avaliação:</strong></p>
    <form method="POST" action="postagem/avaliar.php" class="form-avaliacao">
      <input type="hidden" name="postagem_id" value="<?= $postId ?>">
      <div class="emojis">
        <?php
          $notaUsuario = null;
          $resNota = $conn->query("SELECT nota FROM avaliacoes WHERE postagem_id = $postId AND funcionario_id = $idFuncionario");
          if ($resNota && $row = $resNota->fetch_assoc()) {
            $notaUsuario = $row['nota'];
          }

          $emojis = [
            1 => ['😡', 'Raiva'],
            2 => ['👎', 'Não gostei'],
            3 => ['😐', 'Indiferença'],
            4 => ['👍', 'Gostei'],
            5 => ['😍', 'Adorei']
          ];

          foreach ($emojis as $valor => [$emoji, $titulo]) {
            $destaque = ($notaUsuario == $valor) ? 'style="border:2px solid #007bff; border-radius:5px;"' : '';
            echo "<button type='submit' name='nota' value='$valor' title='$titulo' $destaque>$emoji</button> ";
          }
        ?>
      </div>
    </form>

    <hr>
    <h4>📋 Avaliações recebidas:</h4>
    <?php
      $resAvaliacoes = $conn->query("
        SELECT a.nota, f.nome 
        FROM avaliacoes a 
        JOIN funcionarios f ON a.funcionario_id = f.id 
        WHERE a.postagem_id = $postId
        ORDER BY a.id ASC
      ");
      while ($avaliacao = $resAvaliacoes->fetch_assoc()):
        $emoji = $emojis[$avaliacao['nota']][0] ?? '❓';
    ?>
      <p><?= $emoji ?> <strong><?= htmlspecialchars($avaliacao['nome']) ?></strong></p>
    <?php endwhile; ?>
  </div>
</div>

<div id="comentarios-<?= $postId ?>" class="modal">
  <div class="modal-content">
    <span onclick="fecharModal('comentarios-<?= $postId ?>')" class="close">&times;</span>
    <h3>💬 Comentários</h3>
    <?php foreach ($comentariosPorPostagem[$postId] ?? [] as $comentario): ?>
      <div style="margin-bottom:15px; position:relative;">
        <p><strong><?= htmlspecialchars($comentario['nome']) ?></strong> em <?= date('d/m/Y H:i', strtotime($comentario['data_comentario'])) ?></p>
        <p><?= htmlspecialchars($comentario['texto']) ?></p>

        <?php if ($comentario['funcionario_id'] == $idFuncionario): ?>
          <div style="position:absolute; top:5px; right:5px; display:flex; gap:10px;">
            <button onclick="fecharModal('comentarios-<?= $postId ?>'); abrirModal('editar-comentario-<?= $comentario['id'] ?>')" title="Editar">✏️</button>
            <form method="POST" action="postagem/excluir_comentario.php" onsubmit="return confirm('Excluir este comentário?')">
              <input type="hidden" name="comentario_id" value="<?= $comentario['id'] ?>">
              <button title="Excluir" style="background:none; border:none; cursor:pointer;">🗑️</button>
            </form>
          </div>
        <?php endif; ?>
        <hr>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php foreach ($comentariosPorPostagem[$postId] ?? [] as $comentario): ?>
  <?php if ($comentario['funcionario_id'] != $idFuncionario) continue; ?>
  <div id="editar-comentario-<?= $comentario['id'] ?>" class="modal">
    <div class="modal-content">
      <span onclick="fecharModal('editar-comentario-<?= $comentario['id'] ?>')" class="close">&times;</span>
      <h3>✏️ Editar Comentário</h3>
      <form method="POST" action="postagem/editar_comentario.php">
        <input type="hidden" name="comentario_id" value="<?= $comentario['id'] ?>">
        <textarea name="texto" rows="3" style="width:100%;"><?= htmlspecialchars($comentario['texto']) ?></textarea>
        <button type="submit" style="margin-top:10px;">Salvar</button>
      </form>
    </div>
  </div>
<?php endforeach; ?>
