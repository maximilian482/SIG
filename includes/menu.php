<?php
// menu.php (corrigido e completo)

// Normaliza sessão e prepara variáveis usadas no menu
$idFuncionario = intval($_SESSION['id_funcionario'] ?? $_SESSION['funcionario_id'] ?? 0);
$cpf           = $_SESSION['cpf'] ?? null;
$nomeUsuario   = $_SESSION['usuario'] ?? 'Usuário';
$fotoPerfil    = caminhoFotoPerfil($conn, $idFuncionario);

$cargo         = strtolower($_SESSION['cargo'] ?? '');
$lojaUsuario   = intval($_SESSION['loja'] ?? 0);
$setorUsuario  = intval($_SESSION['id_setor'] ?? 0); // CORRETO

// =====================================================
// 1) Regras de acesso
// =====================================================
$isGerenciaLoja = in_array($cargo, ['gerente', 'subgerente'], true);
$isSuperOuCeo   = in_array($cargo, ['super', 'ceo'], true);

$temAcessoGestao = usuarioTemAcessoGestao($conn, $cpf);

// SUPER/CEO nunca devem ter acesso ao painel da loja
$temAcessoLoja = (!$isSuperOuCeo && temAcesso($conn, $cpf, 'acesso_painel_loja'));

// =====================================================
// 2) Buscar setores liberados
// =====================================================
$setoresLiberadosRaw = usuarioTemSetores($conn, $cpf);
if (!is_array($setoresLiberadosRaw)) $setoresLiberadosRaw = [];

$setoresLiberadosIds = array_unique(
    array_filter(
        array_map('intval', array_column($setoresLiberadosRaw, 'id')),
        fn($id) => $id !== 16 // IGNORA setor Geral
    )
);
$temSetorLiberado = !empty($setoresLiberadosIds);


// =====================================================
// 3) Lógica final de pendências
// =====================================================
$temPendencias = $temSetorLiberado;

$destinoPendencias = '/modulos/chamados_setores.php';


// =====================================================
// 4) Contador de pendências
// =====================================================
$chamados = listarChamados($conn);
$quantPendencias = 0;

$quantPendencias = 0;
foreach ($setoresLiberadosIds as $idSetor) {
    $quantPendencias += contarPendenciasPorSetor($chamados, $idSetor);
}


$textoPendencias = $quantPendencias > 0 
    ? "⏳ Pendências ($quantPendencias)" 
    : "⏳ Pendências (✓)";

// =====================================================
// 5) Minhas Tarefas — contador (CORRIGIDO)
// =====================================================
$quantMinhasTarefas = contarTarefasPendentes($conn, $idFuncionario);

$textoMinhasTarefas = $quantMinhasTarefas > 0
    ? "📝 Minhas Tarefas ($quantMinhasTarefas)"
    : "📝 Minhas Tarefas";

// =====================================================
// 6) Aguardando Avaliação
// =====================================================
$aguardando = aguardando_avaliacao($conn, $idFuncionario, $setorUsuario, $lojaUsuario);

// =====================================================
// 7) Acesso ao módulo Planos de Ação
// =====================================================
$temAcessoPlanosAcao = $isSuperOuCeo;

// =====================================================
// 8) Contador do Trilho
// =====================================================
$quantTrilho = 0;

if (!$isSuperOuCeo && temAcesso($conn, $cpf, 'trilho_motoboy')) {
    $quantTrilho = contarTrilhoPendentes($conn, $idFuncionario);
}

$textoTrilho = $quantTrilho > 0
    ? "🚚 Trilho ($quantTrilho)"
    : "🚚 Trilho";

?>

<header class="menu-header">
  <div class="menu-toggle" onclick="toggleMenu()">☰</div>
</header>

<nav class="menu-lateral" id="menuLateral">
  <ul>    
    <li><a href="/index.php">🏠 Início</a></li>

    <li><a href="/modulos/chamados_publico.php">🛠️ Chamados</a></li>

   <?php if ($temPendencias): ?>
        <li>
            <a href="/modulos/chamados_setores.php">
                <?= htmlspecialchars($textoPendencias, ENT_QUOTES, 'UTF-8') ?>
            </a>
        </li>
    <?php endif; ?>


    <?php if (!$isSuperOuCeo && temAcesso($conn, $cpf, 'trilho_motoboy')): ?>
        <li><a href="/modulos/trilho_motoboy.php"><?= htmlspecialchars($textoTrilho) ?></a></li>
    <?php endif; ?>




    <?php if (!$isSuperOuCeo): ?>
        <li><a href="/modulos/planos_acao/minhas_tarefas.php"><?= htmlspecialchars($textoMinhasTarefas) ?></a></li>
    <?php endif; ?>

    <!-- <?php if ($aguardando > 0 && !$isSuperOuCeo): ?>
        <li><a href="/modulos/planos_acao/minhas_tarefas.php#aguardando">🕒 Aguardando Avaliação (<?= $aguardando ?>)</a></li>
    <?php endif; ?> -->

    <?php if ($isGerenciaLoja && !$isSuperOuCeo): ?>
        <li><a href="/modulos/painel_loja_gerente.php">🏪 Loja</a></li>

    <?php elseif ($temAcessoGestao): ?>
        <li><a href="/modulos/gestao.php">📊 Gestão</a></li>
    <?php endif; ?>

    <?php if ($temAcessoPlanosAcao): ?>
        <li><a href="/modulos/planos_acao/planos_acao_listar.php">📋 Planos de Ação</a></li>
    <?php endif; ?>

    <li><a href="/modulos/mensagem.php">💬 Enviar Mensagem</a></li>
    <li><a href="/modulos/avaliacoes.php">⭐ Avaliações</a></li>
    <li><a href="/modulos/comunidade.php">🌐 Comunidade</a></li>
  </ul>
</nav>

<script>
  function toggleMenu() {
    document.getElementById('menuLateral').classList.toggle('ativo');
  }

  document.addEventListener('click', function (e) {
    const menu = document.getElementById('menuLateral');
    const toggle = document.querySelector('.menu-toggle');

    if (!menu.contains(e.target) && !toggle.contains(e.target)) {
      menu.classList.remove('ativo');
    }
  });
</script>
