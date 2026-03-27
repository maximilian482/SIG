<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

include '../includes/menu.php';
include '../includes/head.php';
include '../perfil/menu_perfil.php';

$usuarioId = $_SESSION['funcionario_id'] ?? 0;
if ($usuarioId <= 0) {
  die("Acesso restrito. Faça login novamente.");
}

// Definições de paginação
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($limit <= 0) $limit = 5;
if ($page <= 0) $page = 1;

$offset = ($page - 1) * $limit;

// Total de mensagens
$stmtTotal = $conn->prepare("SELECT COUNT(*) AS total FROM mensagens WHERE destinatario_id = ?");
$stmtTotal->bind_param("i", $usuarioId);
$stmtTotal->execute();
$totalMensagens = $stmtTotal->get_result()->fetch_assoc()['total'];
$stmtTotal->close();

// Total de não lidas
$stmtNL = $conn->prepare("SELECT COUNT(*) AS nao_lidas FROM mensagens WHERE destinatario_id = ? AND lida = 0");
$stmtNL->bind_param("i", $usuarioId);
$stmtNL->execute();
$totalNaoLidas = $stmtNL->get_result()->fetch_assoc()['nao_lidas'];
$stmtNL->close();

$totalPaginas = ceil($totalMensagens / $limit);

// Buscar mensagens paginadas
$stmt = $conn->prepare("
  SELECT m.id, m.conteudo, m.data, m.lida, m.arquivo, f.nome AS remetente
  FROM mensagens m
  JOIN funcionarios f ON f.id = m.remetente_id
  WHERE m.destinatario_id = ?
  ORDER BY m.data DESC
  LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $usuarioId, $limit, $offset);
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="stylesheet" href="../css/mensagem_perfil.css">
  <meta charset="UTF-8">
  <title>Minhas Mensagens</title> 
</head>
<body>
<main class="layout-principal">
  <h1>
    📧 Minhas Mensagens
    <span id="contador-nao-lidas">(<?= $totalNaoLidas ?> não lidas)</span>
  </h1>

  <?php if ($res->num_rows === 0): ?>
    <p>Você não possui mensagens.</p>
  <?php else: ?>
    <ul class="mensagens">
      <?php while ($msg = $res->fetch_assoc()): ?>
        <li class="<?= $msg['lida'] ? 'lida' : 'nao-lida' ?>" data-id="<?= $msg['id'] ?>">
  <div class="cabecalho">
    <span><strong>De:</strong> <?= htmlspecialchars($msg['remetente']) ?></span>
    <span class="data"><?= date('d/m/Y H:i', strtotime($msg['data'])) ?></span>
  </div>
  <div class="conteudo"><?= nl2br(htmlspecialchars($msg['conteudo'])) ?></div>

 <div class="rodape-card">
  <div class="celula-download">
    <?php if (!empty($msg['arquivo'])): ?>
      <a class="btn-download" href="<?= htmlspecialchars($msg['arquivo']) ?>" download>📎 Baixar documento</a>
    <?php endif; ?>
  </div>
  <div class="celula-lido">
    <?php if (empty($msg['lida'])): ?>
      <button class="btn-lido" onclick="marcarComoLido('mensagem', <?= $msg['id'] ?>)">✔️ Marcar como lida</button>
    <?php endif; ?>
  </div>
  <div class="celula-excluir">
    <button class="btn-excluir" onclick="excluirMensagem(<?= $msg['id'] ?>)">🗑️</button>
  </div>
</div>




</li>


      <?php endwhile; ?>
    </ul>
  <?php endif; ?>

  <!-- Paginação -->
  <div class="paginacao">
    <?php if ($page > 1): ?>
      <a href="?page=<?= $page-1 ?>&limit=<?= $limit ?>">⬅️</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
      <?php if ($i == $page): ?>
        <span class="ativo"><?= $i ?></span>
      <?php else: ?>
        <a href="?page=<?= $i ?>&limit=<?= $limit ?>"><?= $i ?></a>
      <?php endif; ?>
    <?php endfor; ?>

    <?php if ($page < $totalPaginas): ?>
      <a href="?page=<?= $page+1 ?>&limit=<?= $limit ?>">➡️</a>
    <?php endif; ?>
  </div>

  <!-- seletor de limite -->
  <div class="paginacao">
    <form method="get" style="display:inline-block;">
      <label style="margin-right:10px;">Mostrar:
        <select name="limit" onchange="this.form.submit()" style="padding:6px; border-radius:6px; border:1px solid #ccc;">
          <option value="5" <?= $limit==5?'selected':'' ?>>5</option>
          <option value="10" <?= $limit==10?'selected':'' ?>>10</option>
          <option value="20" <?= $limit==20?'selected':'' ?>>20</option>
        </select>
        por página
      </label>
      <input type="hidden" name="page" value="1">
    </form>
  </div>
</main>

<?php include __DIR__ . '/../includes/scripts.php'; ?>
<script src="../js/mensagens_perfil.js"></script>

</body>
</html>
