<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

$idFuncionario = $_SESSION['id_funcionario'] ?? null;
$postagemId    = isset($_POST['postagem_id']) ? (int)$_POST['postagem_id'] : null;
$nota          = isset($_POST['nota']) ? (int)$_POST['nota'] : null;

if (!$idFuncionario || !$postagemId || !$nota || $nota < 1 || $nota > 5) {
  exit('Requisição inválida');
}

// Garante uma avaliação por usuário (chave única no banco)
$stmt = $conn->prepare("INSERT INTO avaliacoes (postagem_id, funcionario_id, nota)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE nota = VALUES(nota)");
$stmt->bind_param("iii", $postagemId, $idFuncionario, $nota);
$stmt->execute();

// Emojis para render
$emojis = [1=>['😡','Raiva'],2=>['👎','Não gostei'],3=>['😐','Indiferença'],4=>['👍','Gostei'],5=>['😍','Adorei']];

// Retorna só a lista atualizada
$res = $conn->query("
  SELECT a.nota, f.nome
  FROM avaliacoes a
  JOIN funcionarios f ON a.funcionario_id = f.id
  WHERE a.postagem_id = $postagemId
  ORDER BY a.id ASC
");
while ($avaliacao = $res->fetch_assoc()) {
  $emoji = $emojis[$avaliacao['nota']][0] ?? '❓';
  echo '<div class="avaliacao-item">';
  echo '<span>' . $emoji . '</span> ';
  echo '<strong>' . htmlspecialchars($avaliacao['nome']) . '</strong>';
  echo '</div>';
}
