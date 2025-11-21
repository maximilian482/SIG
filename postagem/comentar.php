<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

$idFuncionario = $_SESSION['id_funcionario'] ?? null;
$postagemId = $_POST['postagem_id'] ?? null;
$texto = trim($_POST['texto'] ?? '');

if (!$idFuncionario || !$postagemId || $texto === '') {
  exit('Comentário inválido');
}

// Insere o comentário
$stmt = $conn->prepare("
  INSERT INTO comentarios (postagem_id, funcionario_id, texto, data_comentario)
  VALUES (?, ?, ?, NOW())
");
$stmt->bind_param("iis", $postagemId, $idFuncionario, $texto);
$stmt->execute();

// Busca comentários atualizados
$res = $conn->query("
  SELECT c.id, c.texto, c.data_comentario, f.nome, c.funcionario_id
  FROM comentarios c
  JOIN funcionarios f ON c.funcionario_id = f.id
  WHERE c.postagem_id = $postagemId
  ORDER BY c.data_comentario ASC
");

// Gera apenas os blocos de comentários (sem estrutura de modal)
while ($comentario = $res->fetch_assoc()) {
  echo '<div class="comentario">';
  echo '<div class="comentario-topo">';
  echo '<div class="autor-info">';
  echo '<strong>' . htmlspecialchars($comentario['nome']) . '</strong>';
  echo '<span class="data-comentario">' . date('d/m/Y H:i', strtotime($comentario['data_comentario'])) . '</span>';
  echo '</div>';

  // Ações do autor (editar/excluir)
  if ($comentario['funcionario_id'] == $idFuncionario) {
    echo '<div class="comentario-acoes">';
    echo '<button onclick="abrirModal(\'editar-comentario-' . $comentario['id'] . '\')" title="Editar"><i class="fas fa-pen"></i></button>';
    echo '<form method="POST" action="postagem/excluir_comentario.php" onsubmit="return confirm(\'Excluir este comentário?\')">';
    echo '<input type="hidden" name="comentario_id" value="' . $comentario['id'] . '">';
    echo '<button title="Excluir"><i class="fas fa-trash"></i></button>';
    echo '</form>';
    echo '</div>';
  }

  echo '</div>'; // comentario-topo
  echo '<p>' . htmlspecialchars($comentario['texto']) . '</p>';
  echo '</div>'; // comentario
}
?>
