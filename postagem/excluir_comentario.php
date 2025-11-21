<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

$idFuncionario = $_SESSION['id_funcionario'] ?? null;
$comentarioId  = $_POST['comentario_id'] ?? null;

if (!$idFuncionario || !$comentarioId) {
  exit('Requisição inválida');
}

// Verifica se o comentário pertence ao funcionário
$stmt = $conn->prepare("SELECT postagem_id, funcionario_id FROM comentarios WHERE id = ?");
$stmt->bind_param("i", $comentarioId);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res || $res['funcionario_id'] != $idFuncionario) {
  exit('Permissão negada');
}

$postagemId = $res['postagem_id'];

// Exclui o comentário
$stmt = $conn->prepare("DELETE FROM comentarios WHERE id = ?");
$stmt->bind_param("i", $comentarioId);
$stmt->execute();

// Busca comentários atualizados
$res = $conn->query("
  SELECT c.id, c.texto, c.data_comentario, c.editado_em, f.nome, c.funcionario_id
  FROM comentarios c
  JOIN funcionarios f ON c.funcionario_id = f.id
  WHERE c.postagem_id = $postagemId
  ORDER BY c.data_comentario ASC
");

// Retorna apenas a lista de comentários
while ($comentario = $res->fetch_assoc()) {
  echo '<div class="comentario">';
  echo '  <div class="comentario-topo">';
  echo '    <div class="autor-info">';
  echo '      <strong>' . htmlspecialchars($comentario['nome']) . '</strong>';
  echo '      <span class="data-comentario">';
  echo            date('d/m/Y H:i', strtotime($comentario['data_comentario']));
  if (!empty($comentario['editado_em'])) {
    echo ' (editado em ' . date('d/m/Y H:i', strtotime($comentario['editado_em'])) . ')';
  }
  echo '      </span>';
  echo '    </div>';

  if ($comentario['funcionario_id'] == $idFuncionario) {
    echo '    <div class="comentario-acoes">';
    echo '      <button onclick="abrirModal(\'editar-comentario-' . $comentario['id'] . '\')" title="Editar">';
    echo '        <i class="fas fa-edit"></i>';
    echo '      </button>';
    echo '      <form method="POST" action="postagem/excluir_comentario.php" class="excluir-comentario-form">';
    echo '        <input type="hidden" name="comentario_id" value="' . $comentario['id'] . '">';
    echo '        <button type="submit" title="Excluir"><i class="fas fa-trash"></i></button>';
    echo '      </form>';
    echo '    </div>';
  }

  echo '  </div>';
  echo '  <p>' . htmlspecialchars($comentario['texto']) . '</p>';
  echo '</div>';
}
?>
