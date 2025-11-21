<?php
session_start();
require_once 'includes/funcoes.php';
$conn = conectar();

include 'includes/head.php';
include 'includes/menu.php';
include 'perfil/menu_perfil.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Dados do funcionário
$idFuncionario = $_SESSION['id_funcionario'];
$usuario       = $_SESSION['usuario'];
$cpf           = $_SESSION['cpf'] ?? '';
$cargo         = $_SESSION['cargo'] ?? '';
$caminhoFoto   = caminhoFotoPerfil($conn, $idFuncionario);

// Definir acesso total (ADM)
$_SESSION['acessoTotal'] = in_array(normalizar($cargo), ['adm', 'super', 'ceo']);

// Definir acesso à gestão
// $_SESSION['acesso_gestao'] = $_SESSION['acessoTotal']
//   || temAcesso($conn, $cpf, 'dashboard')
//   || temAcesso($conn, $cpf, 'gestao');

// Buscar postagens
$stmt = $conn->prepare("
  SELECT p.id, p.conteudo, p.imagem, p.data_postagem, f.nome, f.id AS autor_id
  FROM postagens p 
  JOIN funcionarios f ON p.funcionario_id = f.id 
  WHERE p.visivel = 1 
  ORDER BY p.data_postagem DESC
");
$stmt->execute();
$postagens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Buscar comentários
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

// Buscar avaliações
$contadores = [];
$res = $conn->query("
  SELECT postagem_id, COUNT(*) AS total, AVG(nota) AS media 
  FROM avaliacoes 
  GROUP BY postagem_id
");
while ($row = $res->fetch_assoc()) {
  $contadores[$row['postagem_id']] = [
    'avaliacoes' => $row['total'],
    'media' => round($row['media'], 1)
  ];
}

?>

<?php if (!empty($_SESSION['erro_postagem'])): ?>
  <div class="mensagem-erro">
    <i class="fas fa-exclamation-circle"></i>
    <?= htmlspecialchars($_SESSION['erro_postagem']) ?>
  </div>
  <?php unset($_SESSION['erro_postagem']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['sucesso_postagem'])): ?>
  <div class="mensagem-sucesso">
    <i class="fas fa-check-circle"></i>
    <?= htmlspecialchars($_SESSION['sucesso_postagem']) ?>
  </div>
  <?php unset($_SESSION['sucesso_postagem']); ?>
<?php endif; ?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Feed</title>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
</head>
<body>

<main>
  <section id="feed">
    <div class="logo-mural">
       <img src="imagens/logo.jpg" alt="Logo Atacadão Souza Farma Express">
     <h2> Mural da Empresa</h2>
    </div>
  

   <!-- Formulário de nova postagem -->
  <!-- Formulário de nova postagem -->
<h3>📝 Publicar</h3>
<form method="POST" action="postagem/postar.php">
  <!-- Container do editor Quill -->
  <div id="editor" style="height:150px;"></div>

  <!-- Campo oculto que receberá o HTML -->
  <input type="hidden" name="conteudo" id="conteudo">

  <button type="submit" style="margin-top:15px;">Publicar</button>
</form>

    
    <!-- Loop de postagens -->
    <?php foreach ($postagens as $post): ?>
  <?php
    $postId     = $post['id'];
    $comentarios = count($comentariosPorPostagem[$postId] ?? []);
    $avaliacoes = $contadores[$postId]['avaliacoes'] ?? 0;
    $media      = $contadores[$postId]['media'] ?? '-';
  ?>
  <div class="post">
    <div class="post-topo">
  <div class="autor-e-botoes">
    <strong><?= htmlspecialchars($post['nome']) ?></strong>
    <?php if (isset($post['autor_id']) && $post['autor_id'] == $idFuncionario): ?>
  <form method="POST" action="postagem/excluir_postagem.php" class="excluir-postagem-form">
  <input type="hidden" name="postagem_id" value="<?= $postId ?>">
<button type="submit" title="Excluir" class="btn-icon">
  <i class="fa-solid fa-trash"></i>
</button>
</form>


<?php endif; ?>

  </div>
  <span class="data-postagem"><?= date('d/m/Y H:i', strtotime($post['data_postagem'])) ?></span>
</div>

<div>
  <?= strip_tags($post['conteudo'], '<p><br><b><i><u><strong><em><img><ul><ol><li><a>') ?>
</div>
<?php if (!empty($post['imagem'])): ?>
  <div style="margin-top:10px;">
    <img src="uploads/<?= htmlspecialchars($post['imagem']) ?>" alt="Imagem da postagem" style="max-width:100%; height:auto; border-radius:8px;">
  </div>
<?php endif; ?>


    <!-- Botões de ação -->
    <div style="margin-top:15px; display:flex; justify-content:center; gap:30px;">
      <button onclick="abrirModal('comentarios-<?= $postId ?>')">💬 Comentários (<?= $comentarios ?>)</button>
      <button onclick="abrirModal('avaliacoes-<?= $postId ?>')" id="btn-avaliacoes-<?= $postId ?>">
        ⭐ Avaliações (<?= $avaliacoes ?>) Média: <?= $media ?>
      </button>
    </div>
     

    <!-- Formulário de comentário -->
    <form method="POST" action="postagem/comentar.php" class="comentario-form">
      <input type="hidden" name="postagem_id" value="<?= $postId ?>">
      <input type="text" name="texto" placeholder="Escreva um comentário..." required>
      <button type="submit">Comentar</button>
    </form>

    <!-- Modais de edição, avaliação e comentários -->
    

    <div id="avaliacoes-<?= $postId ?>" class="modal">
      <div class="modal-content">
        <span onclick="fecharModal('avaliacoes-<?= $postId ?>')" class="close">&times;</span>
        <h3>⭐ Avaliações</h3>

        <!-- Formulário de avaliação -->
        <p><strong>Faça sua avaliação:</strong></p>
        <form method="POST" action="postagem/avaliar.php" class="form-avaliacao">
          <input type="hidden" name="postagem_id" value="<?= $postId ?>">
          <input type="hidden" name="nota" value="" id="nota-<?= $postId ?>">
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
                echo "<button type='button' class='btn-nota' data-nota='$valor' title='$titulo' $destaque>$emoji</button> ";
              }
            ?>
          </div>
        </form>


        <!-- Lista de avaliações -->
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



    <div id="comentarios-<?= $postId ?>" class="modal">
      <div class="modal-content">
        <span onclick="fecharModal('comentarios-<?= $postId ?>')" class="close">&times;</span>
        <h3>💬 Comentários</h3>

        <div class="lista-comentarios">
      <?php foreach ($comentariosPorPostagem[$postId] ?? [] as $comentario): ?>
        <div class="comentario">
          <div class="comentario-topo">
            <div class="autor-info">
              <strong><?= htmlspecialchars($comentario['nome']) ?></strong>
              <span class="data-comentario">
                <?= date('d/m/Y H:i', strtotime($comentario['data_comentario'])) ?>
                <?php if (!empty($comentario['editado_em'])): ?>
                  (editado em <?= date('d/m/Y H:i', strtotime($comentario['editado_em'])) ?>)
                <?php endif; ?>
              </span>
            </div>

            <?php if ($comentario['funcionario_id'] == $idFuncionario): ?>
              <div class="comentario-acoes">
              <button onclick="abrirModal('editar-comentario-<?= $comentario['id'] ?>')" title="Editar">
                <i class="fas fa-edit"></i>
              </button>
              <form method="POST" action="postagem/excluir_comentario.php" class="excluir-comentario-form">
                <input type="hidden" name="comentario_id" value="<?= $comentario['id'] ?>">
                <button type="submit" title="Excluir"><i class="fas fa-trash"></i></button>
              </form>

              </div>
            <?php endif; ?>
          </div>

          <p><?= htmlspecialchars($comentario['texto']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>


<!-- Modal Edição comentário -->
<?php foreach ($comentariosPorPostagem as $comentarios): ?>
  <?php foreach ($comentarios as $comentario): ?>
    <?php if ($comentario['funcionario_id'] != $idFuncionario) continue; ?>
    <div id="editar-comentario-<?= $comentario['id'] ?>" class="modal">
      <div class="modal-content">
        <span onclick="fecharModal('editar-comentario-<?= $comentario['id'] ?>')" class="close">&times;</span>
        <h3>✏️ Editar Comentário</h3>
        <form class="editar-comentario-form" method="POST" action="postagem/editar_comentario.php">
          <input type="hidden" name="comentario_id" value="<?= $comentario['id'] ?>">
          <input type="hidden" name="postagem_id" value="<?= $postId ?>">
          <textarea name="texto" rows="3" required><?= htmlspecialchars($comentario['texto']) ?></textarea>
          <button type="submit">Salvar</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
<?php endforeach; ?>



  </div>
<?php endforeach; ?>

  </section>
</main>

<!-- Scripts -->
<!-- Seus scripts (ordem segura) -->
<script src="js/modal.js"></script>    
<script src="js/comentario.js"></script>    
<script src="js/editar_comentario.js"></script>       
<script src="js/avaliacao.js"></script> 
<script src="js/excluir_comentario.js"></script> 
<script src="js/excluir_postagem.js"></script> 
<script src="js/quill.js"></script>
<script src="postagem/quill-editar.js.php"></script>


<?php include 'includes/scripts.php' ?>

</body>
</html>