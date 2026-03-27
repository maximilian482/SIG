<?php
session_start();
require_once 'includes/funcoes.php';
$conn = conectar();


// Dados do funcionário
$idFuncionario = $_SESSION['id_funcionario'] ?? null;
$usuario       = $_SESSION['usuario'] ?? '';
$cpf           = $_SESSION['cpf'] ?? '';
$cargo         = $_SESSION['cargo'] ?? '';
$caminhoFoto   = caminhoFotoPerfil($conn, $idFuncionario);

// Definir acesso total (ADM)
$_SESSION['acessoTotal'] = in_array(normalizar($cargo), ['adm', 'super', 'ceo']);

include 'includes/head.php';
include 'includes/menu.php';
include 'perfil/menu_perfil.php';

/* ============================================================
   POSTAGENS
============================================================ */
$stmt = $conn->prepare("
  SELECT p.id, p.conteudo, p.imagem, p.data_postagem, f.nome, f.id AS autor_id
  FROM postagens p 
  JOIN funcionarios f ON p.funcionario_id = f.id 
  WHERE p.visivel = 1 
  ORDER BY p.data_postagem DESC
");
$stmt->execute();
$postagens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ============================================================
   COMENTÁRIOS
============================================================ */
$comentariosPorPostagem = [];
$res = $conn->query("
  SELECT c.id, c.postagem_id, c.funcionario_id, c.texto, c.data_comentario, f.nome 
  FROM comentarios c 
  JOIN funcionarios f ON c.funcionario_id = f.id 
  WHERE c.visivel = 1
  ORDER BY c.data_comentario ASC
");

while ($row = $res->fetch_assoc()) {
  $comentariosPorPostagem[$row['postagem_id']][] = $row;
}

/* ============================================================
   AVALIAÇÕES
============================================================ */
$contadores = [];
$res = $conn->query("
  SELECT postagem_id, COUNT(*) AS total, AVG(nota) AS media 
  FROM avaliacoes 
  GROUP BY postagem_id
");

while ($row = $res->fetch_assoc()) {
  $contadores[$row['postagem_id']] = [
    'avaliacoes' => $row['total'],
    'media'      => round($row['media'], 1)
  ];
}

/* ============================================================
   TAREFAS PENDENTES (Planos de Ação)
============================================================ */

$idFuncionario = intval($_SESSION['id_funcionario'] ?? 0);
$cpf           = $_SESSION['cpf'] ?? '';
$cargo         = strtolower($_SESSION['cargo'] ?? '');
$lojaUsuario   = intval($_SESSION['loja'] ?? 0);
$setorUsuario  = intval($_SESSION['id_setor'] ?? 0);

$tarefasPendentes = contarTarefasPendentes($conn, $idFuncionario);


/* ============================================================
   Trilho PENDENTES 
   ============================================================ */
$trilhoPendentes = 0;
if (!$isSuperOuCeo && temAcesso($conn, $cpf, 'trilho_motoboy')) {
    $trilhoPendentes = contarTrilhoPendentes($conn);
}


?>

<?php
$temPendencias = $pendenciasTotal > 0;
$temTarefas    = $tarefasPendentes > 0;
$temTrilho     = $trilhoPendentes > 0;

if ($temPendencias || $temTarefas || $temTrilho):
?>
<div class="alerta-pendencias">
  <strong>⚠️ 
    <?php
      $mensagens = [];

      if ($pendenciasTotal > 0) {
          $mensagens[] = "{$pendenciasTotal} pendência" . ($pendenciasTotal > 1 ? "s" : "");
      }

      if ($tarefasPendentes > 0) {
          $mensagens[] = "{$tarefasPendentes} tarefa" . ($tarefasPendentes > 1 ? "s" : "");
      }

      if ($trilhoPendentes > 0) {
          $mensagens[] = "{$trilhoPendentes} entrega" . ($trilhoPendentes > 1 ? "s" : "") . " no Trilho";
      }

      echo "Você possui " . implode(" e ", $mensagens) . "!";
    ?>
  </strong>
</div>
<?php endif; ?>



 
<!-- AVISO DA COMUNIDADE (sempre aparece) -->
<div class="alerta-comunidade">
  <strong>🎉 Confira as novidades do mês!</strong>
  <p>Acesse o menu <strong>Comunidade</strong> para ver os aniversariantes e o tempo de empresa deste mês.</p>
  <a href="../modulos/comunidade.php#aniversario" class="btn-alerta">Ver aniversariantes</a>
</div>


<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Feed</title>

  <!-- Quill -->
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

  <link rel="stylesheet" href="css/feed.css">
</head>
<body>

<main>
<section id="feed">

  <!-- LOGO -->
  <div class="logo-mural">
    <img src="imagens/logo.jpg" alt="Logo Atacadão Souza Farma Express">
    <h2>Mural da Souza Farma</h2>
  </div>

  <!-- NOVA POSTAGEM -->
  <h3>📝 Publicar</h3>

    <form method="POST" action="postagem/postar.php">
      <div id="toolbar">
        <span class="ql-formats">
          <select class="ql-header">
            <option selected></option>
            <option value="1"></option>
            <option value="2"></option>
            <option value="3"></option>
          </select>
          <select class="ql-font"></select>
        </span>

        <span class="ql-formats">
          <button class="ql-bold"></button>
          <button class="ql-italic"></button>
          <button class="ql-underline"></button>
          <button class="ql-strike"></button>
        </span>

        <span class="ql-formats">
          <button class="ql-blockquote"></button>
          <button class="ql-code-block"></button>
        </span>

        <span class="ql-formats">
          <button class="ql-list" value="ordered"></button>
          <button class="ql-list" value="bullet"></button>
          <button class="ql-indent" value="-1"></button>
          <button class="ql-indent" value="1"></button>
        </span>

        <span class="ql-formats">
          <select class="ql-align"></select>
          <select class="ql-color"></select>
          <select class="ql-background"></select>
        </span>

        <span class="ql-formats">
          <button class="ql-link"></button>
          <button class="ql-image"></button>
          <!-- <button class="ql-video"></button> -->
        </span>
      </div>

      <div id="editor" style="height:150px;"></div>

      <input type="hidden" name="conteudo" id="conteudo">
      <button type="submit" style="margin-top:15px;">Publicar</button>
    </form>


  <hr>

  <!-- LISTA DE POSTAGENS -->
  <?php foreach ($postagens as $post): ?>
  <?php
    $postId = $post['id'];
    $comentarios = count($comentariosPorPostagem[$postId] ?? []);
    $avaliacoes = $contadores[$postId]['avaliacoes'] ?? 0;
    $media      = $contadores[$postId]['media'] ?? '-';
  ?>

  <div class="post">

    <!-- TOPO -->
    <div class="post-topo">
      <div class="autor-e-botoes">
        <strong><?= htmlspecialchars($post['nome']) ?></strong>

        <!-- BOTÕES DO AUTOR -->
        <?php if ($post['autor_id'] == $idFuncionario): ?>

          <!-- EDITAR -->
          <button onclick="abrirModal('editar-postagem-<?= $postId ?>')" class="btn-icon">
            <i class="fa-solid fa-pen"></i>
          </button>

          <!-- EXCLUIR -->
          <form method="POST" action="postagem/excluir_postagem.php" class="excluir-postagem-form">
            <input type="hidden" name="postagem_id" value="<?= $postId ?>">
            <button type="submit" title="Excluir" class="btn-icon">
              <i class="fa-solid fa-trash"></i>
            </button>
          </form>

        <?php endif; ?>
      </div>

      <span class="data-postagem">
        <?= date('d/m/Y H:i', strtotime($post['data_postagem'])) ?>
      </span>
    </div>

    <!-- CONTEÚDO -->
    <div class="conteudo-post">
      <?= htmlspecialchars_decode($post['conteudo']) ?>
    </div>

    <!-- IMAGEM ANTIGA (opcional) -->
    <?php if (!empty($post['imagem'])): ?>
      <div style="margin-top:10px;">
        <img src="uploads/<?= htmlspecialchars($post['imagem']) ?>" style="max-width:100%; border-radius:8px;">
      </div>
    <?php endif; ?>

    <!-- BOTÕES -->
    <div class="acoes-post">
      <button onclick="abrirModal('comentarios-<?= $postId ?>')">
        💬 Comentários (<?= $comentarios ?>)
      </button>

      <button onclick="abrirModal('avaliacoes-<?= $postId ?>')">
        ⭐ Avaliações (<?= $avaliacoes ?>) Média: <?= $media ?>
      </button>
    </div>

    <!-- FORMULÁRIO DE COMENTÁRIO -->
    <form method="POST" action="postagem/comentar.php" class="comentario-form">
      <input type="hidden" name="postagem_id" value="<?= $postId ?>">
      <input type="text" name="texto" placeholder="Escreva um comentário..." required>
      <button type="submit">Comentar</button>
    </form>

    <!-- MODAL DE EDIÇÃO DA POSTAGEM -->
    <div id="editar-postagem-<?= $postId ?>" class="modal">
      <div class="modal-content">
        <span onclick="fecharModal('editar-postagem-<?= $postId ?>')" class="close">&times;</span>

        <h3>✏️ Editar Postagem</h3>

        <form id="form-editar-<?= $postId ?>" method="POST" action="postagem/editar_postagem.php">

          <input type="hidden" name="postagem_id" value="<?= $postId ?>">
          <input type="hidden" name="conteudo" id="conteudo-editar-<?= $postId ?>">

          <div id="editor-editar-<?= $postId ?>" class="editor-editar" style="height:200px;">
            <?= htmlspecialchars_decode($post['conteudo']) ?>
          </div>

          <button type="submit" class="btn-salvar-edicao">Salvar Alterações</button>
        </form>
      </div>
    </div>

    <!-- MODAL DE AVALIAÇÕES -->
    <div id="avaliacoes-<?= $postId ?>" class="modal">
      <div class="modal-content">
        <span onclick="fecharModal('avaliacoes-<?= $postId ?>')" class="close">&times;</span>
        <h3>⭐ Avaliações</h3>

        <form method="POST" action="postagem/avaliar.php" class="form-avaliacao">
          <input type="hidden" name="postagem_id" value="<?= $postId ?>">
          <input type="hidden" name="nota" id="nota-<?= $postId ?>">

          <div class="emojis">
            <?php
              $notaUsuario = null;
              $resNota = $conn->query("SELECT nota FROM avaliacoes WHERE postagem_id = $postId AND funcionario_id = $idFuncionario");
              if ($resNota && $row = $resNota->fetch_assoc()) { $notaUsuario = $row['nota']; }

              $emojis = [
                1 => ['😡', 'Raiva'],
                2 => ['👎', 'Não gostei'],
                3 => ['😐', 'Indiferença'],
                4 => ['👍', 'Gostei'],
                5 => ['😍', 'Adorei']
              ];

              foreach ($emojis as $valor => [$emoji, $titulo]) {
                $destaque = ($notaUsuario == $valor) ? 'style="border:2px solid #007bff; border-radius:5px;"' : '';
                echo "<button type='button' class='btn-nota' data-nota='$valor' title='$titulo' $destaque>$emoji</button>";
              }
            ?>
          </div>
        </form>

        <hr>

        <h4>📋 Avaliações recebidas:</h4>
        <div class="lista-avaliacoes">
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
            <div class="avaliacao-item">
              <span><?= $emoji ?></span>
              <strong><?= htmlspecialchars($avaliacao['nome']) ?></strong>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>

    <!-- MODAL DE COMENTÁRIOS -->
    <div id="comentarios-<?= $postId ?>" class="modal">
      <div class="modal-content">
        <span onclick="fecharModal('comentarios-<?= $postId ?>')" class="close">&times;</span>
        <h3>💬 Comentários</h3>

        <div class="lista-comentarios">
          <?php foreach ($comentariosPorPostagem[$postId] ?? [] as $comentario): ?>
            <div class="comentario">
              <div class="comentario-topo">
                <strong><?= htmlspecialchars($comentario['nome']) ?></strong>
                <span><?= date('d/m/Y H:i', strtotime($comentario['data_comentario'])) ?></span>
              </div>
              <p><?= htmlspecialchars($comentario['texto']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </div>
  <?php endforeach; ?>

</section>
</main>

<!-- SCRIPTS -->
<script src="js/modal.js"></script>
<script src="js/comentario.js"></script>
<script src="js/editar_comentario.js"></script>
<script src="js/avaliacao.js"></script>
<script src="js/excluir_comentario.js"></script>
<script src="js/excluir_postagem.js"></script>
<!-- <script src="js/quill.js"></script> -->
<script src="postagem/quill-editar.js.php"></script>

<?php include 'includes/scripts.php' ?>

<script>
  var quill = new Quill('#editor', {
    theme: 'snow',
    modules: {
      toolbar: '#toolbar'
    }
  });

  document.querySelector('form').addEventListener('submit', function() {
    document.querySelector('#conteudo').value = quill.root.innerHTML;
  });
</script>



</body>
</html>
