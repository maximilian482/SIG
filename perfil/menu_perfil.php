<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../dados/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$conn = conectar();

// Verifica login
$usuarioLogado = isset($_SESSION['usuario_id']) || isset($_SESSION['id_funcionario']);
if (!$usuarioLogado) {
    header('Location: /login.php');
    exit;
}

// Dados do funcionário
$idFuncionario = $_SESSION['id_funcionario'] ?? null;
$cargo         = strtolower($_SESSION['cargo'] ?? '');
$cpf           = $_SESSION['cpf'] ?? '';
$lojaId        = $_SESSION['loja'] ?? 0;

// FOTO DO FUNCIONÁRIO (usando função padronizada)
$caminhoFoto = caminhoFotoPerfil($conn, $idFuncionario);



/* INTERAÇÕES */
$interacoesTotal = 0;
if ($idFuncionario) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total 
        FROM reconhecimentos 
        WHERE funcionario_id = ? AND lido = 0
    ");
    $stmt->bind_param("i", $idFuncionario);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $interacoesTotal = intval($res['total'] ?? 0);
}

/* MENSAGENS */
$mensagensTotal = 0;
if ($idFuncionario) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total 
        FROM mensagens 
        WHERE destinatario_id = ? AND COALESCE(lida, 0) = 0
    ");
    $stmt->bind_param("i", $idFuncionario);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $mensagensTotal = intval($res['total'] ?? 0);
}

/* TAREFAS */
$setorUsuario = intval($_SESSION['id_setor'] ?? 0);
$quantTarefasPendentes = contarTarefasPendentes($conn, $idFuncionario, $setorUsuario, $lojaId);

/* PENDÊNCIAS */
$isGerenciaLoja = in_array($cargo, ['gerente', 'subgerente'], true);
$isSuperOuCeo   = in_array($cargo, ['super', 'ceo'], true);

$setoresLiberadosRaw = usuarioTemSetores($conn, $cpf);
if (!is_array($setoresLiberadosRaw)) $setoresLiberadosRaw = [];

$setoresLiberadosIds = array_unique(array_map('intval', array_column($setoresLiberadosRaw, 'id')));

$chamados = listarChamados($conn);
$pendenciasTotal = 0;

if ($isGerenciaLoja && !$isSuperOuCeo) {
    $pendenciasTotal = contarChamadosLoja($chamados, intval($lojaId));
} else {
    foreach ($setoresLiberadosIds as $setorId) {
        $pendenciasTotal += contarPendenciasPorSetor($chamados, intval($setorId));
    }
}

$destinoPendencias = null;
if ($isGerenciaLoja && !$isSuperOuCeo) {
    $destinoPendencias = '/modulos/chamados_loja.php';
} elseif (!empty($setoresLiberadosIds)) {
    $destinoPendencias = '/modulos/chamados_setores.php';
}



/* TRILHO */
$trilhoTotal = 0;
if (!$isSuperOuCeo && temAcesso($conn, $cpf, 'trilho_motoboy')) {
    $trilhoTotal = contarTrilhoPendentes($conn);
}

$totalBadge = 
    $interacoesTotal + 
    $mensagensTotal + 
    $pendenciasTotal + 
    $quantTarefasPendentes +
    $trilhoTotal;

?>

<div class="perfil-topo">
  <div class="perfil-container">

    <img src="<?= htmlspecialchars($caminhoFoto) ?>" 
         alt="Perfil" 
         onclick="toggleMenuPerfil()" 
         class="perfil-foto">

    <?php if ($totalBadge > 0): ?>
      <span class="perfil-badge 
            <?= $pendenciasTotal > 0 ? 'badge-red badge-pulse' : '' ?>
            <?= ($pendenciasTotal == 0 && $mensagensTotal > 0) ? 'badge-blue' : '' ?>
            <?= ($pendenciasTotal == 0 && $mensagensTotal == 0) ? 'badge-yellow' : '' ?>">
          <?= $totalBadge ?>
      </span>
    <?php endif; ?>

    <div id="menuPerfil" class="perfil-dropdown">

      <a href="/perfil/perfil.php">👤 Perfil</a>

      <?php if ($interacoesTotal > 0): ?>
        <a href="/modulos/comunidade.php#interacoes">🔔 Interações (<?= $interacoesTotal ?>)</a>
      <?php endif; ?>

      <?php if ($quantTarefasPendentes > 0): ?>
        <a href="/modulos/planos_acao/minhas_tarefas.php">📝 Minhas Tarefas (<?= $quantTarefasPendentes ?>)</a>
      <?php endif; ?>

      <?php if ($mensagensTotal > 0): ?>
        <a href="/perfil/mensagens.php">📧 Mensagens (<?= $mensagensTotal ?>)</a>
      <?php endif; ?>

      <?php if ($pendenciasTotal > 0 && $destinoPendencias): ?>
        <a href="<?= $destinoPendencias ?>">⏳ Pendências (<?= $pendenciasTotal ?>)</a>
      <?php endif; ?>

      <?php if ($trilhoTotal > 0): ?>
          <a href="/modulos/trilho_motoboy.php">🚚 Trilho (<?= $trilhoTotal ?>)</a>
      <?php endif; ?>


      <a href="/logout.php">🚪 Sair</a>

    </div>
  </div>
</div>
