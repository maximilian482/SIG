<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
require_once __DIR__ . '/../dados/conexao.php';

// Verifica se o usuário está logado (pode ser usuário comum ou funcionário)
$usuarioLogado = isset($_SESSION['usuario_id']) || isset($_SESSION['id_funcionario']);

if (!$usuarioLogado) {
  header('Location: /login.php');
  exit;
}

// Foto de perfil padrão
$caminhoFoto = '/imagens/perfil.png';

// Se for funcionário logado, busca foto
$idFuncionario = $_SESSION['id_funcionario'] ?? null;
if ($idFuncionario) {
  $stmt = $conn->prepare("SELECT foto FROM funcionarios WHERE id = ?");
  $stmt->bind_param("i", $idFuncionario);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($res && !empty($res['foto'])) {
    $foto = $res['foto'];
    $caminhoAbsoluto = $_SERVER['DOCUMENT_ROOT'] . "/uploads/" . $foto;
    if (file_exists($caminhoAbsoluto)) {
      $caminhoFoto = "/uploads/" . $foto;
    }
  }
}

// Conta interações recebidas (apenas para funcionário)
$interacoesTotal = 0;
if ($idFuncionario) {
  $stmt = $conn->prepare("SELECT COUNT(*) as total FROM interacoes WHERE funcionario_id = ?");
  $stmt->bind_param("i", $idFuncionario);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  $interacoesTotal = $res['total'] ?? 0;
}
?>
<!-- Ícone de perfil no canto superior direito -->
<div class="perfil-topo">
  <div class="perfil-container">
    <img src="<?= htmlspecialchars($caminhoFoto) ?>" 
         alt="Perfil" 
         onclick="toggleMenuPerfil()" 
         class="perfil-foto">
    <?php if ($interacoesTotal > 0): ?>
      <span class="perfil-badge"><?= $interacoesTotal ?></span>
    <?php endif; ?>
    <div id="menuPerfil" class="perfil-dropdown">
      <a href="/perfil/perfil.php">👤 Perfil</a>
      <?php if ($idFuncionario): ?>
        <a href="/perfil/interacoes.php">🔔 Interações (<?= $interacoesTotal ?>)</a>
      <?php endif; ?>
      <a href="/logout.php">🚪 Sair</a>
    </div>
  </div>
</div>
