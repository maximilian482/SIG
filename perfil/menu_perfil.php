<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
require_once __DIR__ . '/../dados/conexao.php';

$id = $_SESSION['id_funcionario'] ?? null;
$caminhoFoto = '/imagens/perfil.png'; // Caminho padrão para o navegador

if ($id) {
  $stmt = $conn->prepare("SELECT foto FROM funcionarios WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_assoc();

  if ($res && !empty($res['foto'])) {
    $foto = $res['foto'];
    $caminhoAbsoluto = $_SERVER['DOCUMENT_ROOT'] . "/uploads/" . $foto;

    if (file_exists($caminhoAbsoluto)) {
      $caminhoFoto = "/uploads/" . $foto; // Caminho visível no navegador
    }
  }
}

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario']) || !isset($_SESSION['cpf'])) {
  header('Location: /login.php');
  exit;
}
?>
<!-- Ícone de perfil no canto superior direito -->
<div class="perfil-topo">
  <div class="perfil-container">
    <img src="<?= htmlspecialchars($caminhoFoto) ?>" 
         alt="Perfil" 
         onclick="toggleMenuPerfil()" 
         class="perfil-foto">
    <div id="menuPerfil" class="perfil-dropdown">
      <a href="/perfil/perfil.php">👤 Perfil</a>
      <a href="/logout.php">🚪 Sair</a>
    </div>
  </div>
</div>


