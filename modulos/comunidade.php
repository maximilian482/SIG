<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

include '../includes/menu.php';
include '../includes/head.php';
include '../perfil/menu_perfil.php';

// Mês e ano atuais
$mesAtual = (int)date('m');
$anoAtual = (int)date('Y');

// Aniversariantes do mês
$sqlAniversario = "
  SELECT f.id, f.nome, f.cargo_id, c.nome_cargo, f.nascimento, f.sobre_mim, f.foto
  FROM funcionarios f
  LEFT JOIN cargos c ON f.cargo_id = c.id
  WHERE MONTH(f.nascimento) = ?
    AND f.desligamento IS NULL
    AND f.eh_funcionario = 1
";
$stmt = $conn->prepare($sqlAniversario);
$stmt->bind_param("i", $mesAtual);
$stmt->execute();
$aniversariantes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


// Tempo de empresa (contratação no mês atual)
$sqlTempo = "
  SELECT f.id, f.nome, f.cargo_id, c.nome_cargo, f.contratacao, f.sobre_mim, f.foto
  FROM funcionarios f
  LEFT JOIN cargos c ON f.cargo_id = c.id
  WHERE MONTH(f.contratacao) = ?
    AND f.desligamento IS NULL
    AND f.eh_funcionario = 1
";
$stmt = $conn->prepare($sqlTempo);
$stmt->bind_param("i", $mesAtual);
$stmt->execute();
$tempoEmpresa = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


// Interações do usuário logado
$funcionarioId = $_SESSION['funcionario_id'] ?? 0;
$interacoes = [];

if ($funcionarioId > 0) {
  $sql = "
    SELECT r.id, r.tipo, r.data, r.lido, f.nome AS quem_reconheceu
    FROM reconhecimentos r
    LEFT JOIN funcionarios f ON f.id = r.usuario_id
    WHERE r.funcionario_id = ?
      AND f.eh_funcionario = 1
      AND f.desligamento IS NULL
    ORDER BY r.data DESC
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $funcionarioId);
  $stmt->execute();
  $res = $stmt->get_result();
  $interacoes = $res->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
}

$totalInteracoes = count($interacoes);


// Mensagens recebidas
$sqlMsg = "
  SELECT m.id, m.conteudo, m.data, m.lida, f.nome AS remetente
  FROM mensagens m
  JOIN funcionarios f ON f.id = m.remetente_id
  WHERE m.destinatario_id = ?
  ORDER BY m.data DESC
";
$stmt = $conn->prepare($sqlMsg);
$stmt->bind_param("i", $funcionarioId);
$stmt->execute();
$mensagens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Contador de não lidas
$totalMensagensNaoLidas = count(array_filter($mensagens, fn($m) => empty($m['lida'])));


// Lojas para aba Empresa
$sqlLojas = "
  SELECT l.id, l.nome, l.inauguracao, l.telefone_fixo, l.celular, l.foto_fachada,
         (SELECT COUNT(*) FROM funcionarios f WHERE f.loja_id = l.id AND f.desligamento IS NULL) AS qtd_colaboradores
  FROM lojas l
  WHERE l.ativo = 1
  ORDER BY l.nome ASC
";
$stmt = $conn->prepare($sqlLojas);
$stmt->execute();
$resLojas = $stmt->get_result();
$lojas = $resLojas->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$totalLojas = count($lojas);

// Totais para exibir nos títulos das abas
$totalAniversariantes = count($aniversariantes);
$totalTempoEmpresa    = count($tempoEmpresa);

// Função para contar reconhecimentos do mês por tipo
function contarReconhecimentos($conn, $funcionarioId, $ano, $mes, $tipo) {
  $sql = "SELECT COUNT(*) AS total 
          FROM reconhecimentos 
          WHERE funcionario_id = ? 
            AND YEAR(data) = ? 
            AND MONTH(data) = ? 
            AND tipo = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("iiis", $funcionarioId, $ano, $mes, $tipo);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  return $res['total'] ?? 0;
}

// Função para montar caminho da foto de funcionário
function caminhoFoto($fotoBanco) {
  $foto = '/imagens/perfil.png'; // padrão
  if (!empty($fotoBanco)) {
    $foto = (strpos($fotoBanco, '/uploads/') === 0) ? $fotoBanco : '/uploads/' . $fotoBanco;
    $abs = $_SERVER['DOCUMENT_ROOT'] . $foto;
    if (!file_exists($abs)) $foto = '/imagens/perfil.png';
  }
  return $foto;
}

// Função para montar caminho da foto de loja
function caminhoFotoLoja($fotoBanco) {
  $padrao = '/imagens/loja_padrao.jpg'; // coloque uma imagem padrão de fachada
  if (empty($fotoBanco)) return $padrao;
  $foto = (strpos($fotoBanco, '/uploads/') === 0) ? $fotoBanco : '/uploads/lojas/' . ltrim($fotoBanco, '/');
  $abs = $_SERVER['DOCUMENT_ROOT'] . $foto;
  return file_exists($abs) ? $foto : $padrao;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Comunidade</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/comunidade.css">
</head>
<body>

<h2>🌟 Comunidade Souza Farma</h2>

<!-- Navegação das abas -->
<div class="tabs">
  <button class="tablink" onclick="abrirAba(event, 'empresa')">
    🏬 Empresa (<?= $totalLojas ?>)
  </button>
  <button class="tablink" onclick="abrirAba(event, 'aniversario')">
    🎂 Aniversariantes (<?= $totalAniversariantes ?>)
  </button>
  <button class="tablink" onclick="abrirAba(event, 'tempo')">
    🏆 Tempo de Empresa (<?= $totalTempoEmpresa ?>)
  </button>
  <?php
    $interacoes = $interacoes ?? [];
    $totalNaoLidos = 0;
    foreach ($interacoes as $i) {
      if (empty($i['lido']) || $i['lido'] == 0) {
        $totalNaoLidos++;
      }
    }
    ?>
  <button class="tablink" onclick="abrirAba(event, 'interacoes')" id="btn-interacoes">
    💬 Minhas Interações (<?= $totalNaoLidos ?>)
  </button>
</div>

<!-- Aba Empresa -->
<div id="empresa" class="tabcontent">
  <h3>🏬 Nossas Lojas</h3>
  <div class="lista">
    <?php foreach ($lojas as $l): ?>
      <div class="card loja" id="loja-card-<?= (int)$l['id'] ?>">
        <img src="<?= htmlspecialchars(caminhoFotoLoja($l['foto_fachada'])) ?>"
             alt="Fachada da loja"
             class="fachada"
             onclick="abrirLojaDetalhes(<?= (int)$l['id'] ?>)">
        <strong><?= htmlspecialchars($l['nome']) ?></strong><br>
        <span>📅 Inauguração: <?= !empty($l['inauguracao']) ? date('d/m/Y', strtotime($l['inauguracao'])) : '—' ?></span><br>
        <span>👥 Colaboradores: <?= (int)$l['qtd_colaboradores'] ?></span><br>
        <span>☎️ Fixo: <?= htmlspecialchars($l['telefone_fixo'] ?? '—') ?></span><br>
        <span>📱 Celular: <?= htmlspecialchars($l['celular'] ?? '—') ?></span><br>
      </div>
    <?php endforeach; ?>
    <?php if (empty($lojas)): ?>
      <p>Nenhuma loja cadastrada.</p>
    <?php endif; ?>
  </div>
</div>

<!-- Aba Aniversariantes -->
<div id="aniversario" class="tabcontent">
  <h3>🎂 Aniversariantes do Mês</h3>
  <div class="lista">
    <?php foreach ($aniversariantes as $f): 
      $contador = contarReconhecimentos($conn, $f['id'], $anoAtual, $mesAtual, 'aniversario');
      $foto = caminhoFoto($f['foto']);
    ?>
      <div class="card">
        <img src="<?= htmlspecialchars($foto) ?>" alt="Foto"
             onclick="abrirPerfilPublico(<?= (int)$f['id'] ?>)">
        <strong><?= htmlspecialchars($f['nome']) ?></strong>
        <span></span><br>
        <span>🎂 <?= date('d/m', strtotime($f['nascimento'])) ?></span><br>
        <span class="contador">🎉 <?= (int)$contador ?> reconhecimentos</span><br>
        <button class="parabens" onclick="reconhecerFuncionario(<?= (int)$f['id'] ?>, 'aniversario')">
          Dar os parabéns 🎉
        </button>
      </div>
        <?php endforeach; ?>
    <?php if (empty($aniversariantes)): ?>
      <p>Nenhum aniversariante este mês.</p>
    <?php endif; ?>
  </div>
</div>

<!-- Aba Tempo de Empresa -->
<div id="tempo" class="tabcontent">
  <h3>🏆 Tempo de Empresa</h3>
  <div class="lista">
    <?php foreach ($tempoEmpresa as $f): 
      $contador = contarReconhecimentos($conn, $f['id'], $anoAtual, $mesAtual, 'tempo_empresa');
      $anosEmpresa = (date('Y') - date('Y', strtotime($f['contratacao'])));
      $foto = caminhoFoto($f['foto']);
    ?>
      <div class="card">
        <img src="<?= htmlspecialchars($foto) ?>" alt="Foto"
             onclick="abrirPerfilPublico(<?= (int)$f['id'] ?>)">
        <strong><?= htmlspecialchars($f['nome']) ?></strong>
        <span></span><br>
        <span>🏆 <?= (int)$anosEmpresa ?> anos de empresa</span><br>
        <span class="contador">👏 <?= (int)$contador ?> reconhecimentos</span><br>
        <button class="parabens" onclick="reconhecerFuncionario(<?= (int)$f['id'] ?>, 'tempo_empresa')">
          Reconhecer 👏
        </button>
      </div>
    <?php endforeach; ?>
    <?php if (empty($tempoEmpresa)): ?>
      <p>Ninguém completa aniversário de empresa este mês.</p>
    <?php endif; ?>
  </div>
</div>
 
 <!-- Aba Minhas Interações -->
<div id="interacoes" class="tabcontent">
  <h3>💬 Minhas Interações</h3>
  <?php if (empty($interacoes)): ?>
    <p>Você ainda não recebeu nenhuma interação.</p>
  <?php else: ?>
    <div class="lista">
      <?php foreach ($interacoes as $i): ?>
        <?php
          $dataFormatada = date('d/m/Y', strtotime($i['data']));
          $quem = htmlspecialchars($i['quem_reconheceu'] ?? 'Alguém');
          $tipo = $i['tipo'];

          if ($tipo === 'aniversario') {
            $mensagem = "🎂 {$quem} te parabenizou pelo seu aniversário em {$dataFormatada}!";
          } elseif ($tipo === 'tempo_empresa') {
            $mensagem = "🏆 {$quem} reconheceu seu tempo de empresa em {$dataFormatada}.";
          } else {
            $mensagem = "💬 {$quem} enviou um reconhecimento ({$tipo}) em {$dataFormatada}.";
          }
        ?>
        <div class="card interacao" data-id="<?= (int)$i['id'] ?>">
          <div class="conteudo">
            <p><?= $mensagem ?></p>
            <div class="acoes">
              <?php if (empty($i['lido']) || $i['lido'] == 0): ?>
                <button class="btn-lido" onclick="marcarComoLido(<?= (int)$i['id'] ?>)">✔️ Marcar como lido</button>
              <?php endif; ?>
              <!-- <button class="btn-excluir" onclick="excluirInteracao(<?= (int)$i['id'] ?>)">🗑️</button> -->
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>




    

<!-- Modal de loja -->
<div id="lojaModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="fecharLojaModal()">&times;</span>
    <h3 id="lojaNome"></h3>

    <!-- Fachada -->
    <div class="fachada-header">
      <h4>Foto da Fachada</h4>
      <img id="lojaFachada" class="fachada" src="" alt="Fachada da loja">
      <button class="btn" onclick="document.getElementById('uploadFachada').click()">Alterar fachada</button>
      <input id="uploadFachada" type="file" accept="image/*" style="display:none"
             onchange="uploadFachadaLoja(currentLojaId, this.files[0])">
    </div>

    <!-- Galeria -->
    <div class="galeria-header">
      <h4>Galeria</h4>
      <button class="btn" onclick="document.getElementById('uploadInput').click()">Adicionar foto</button>
      <input id="uploadInput" type="file" accept="image/*" style="display:none"
             onchange="uploadFotoLoja(currentLojaId, this.files[0])">
    </div>
    <div id="lojaGaleria" class="galeria"></div>
  </div>
</div>


<!-- Modal de perfil -->
<div id="perfilModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="fecharPerfilModal()">&times;</span>
    <div id="perfilInfo"></div>
  </div>
</div>

<!-- Modal do Carrossel -->
<div id="carouselModal" class="modal">
  <span class="close" onclick="fecharCarousel()">✖ Fechar</span>
  <div class="carousel-content">
    <img id="carouselImage" src="" alt="Imagem da loja">
    <a class="prev" onclick="mudarImagem(-1)">&#10094;</a>
    <a class="next" onclick="mudarImagem(1)">&#10095;</a>
    <button class="delete-btn" onclick="excluirImagemAtual()">🗑 Excluir</button>
  </div>
</div>


<script src="../js/comunidade.js"></script>


  <script>
  function abrirAba(event, nomeAba) {
    // Oculta todas as abas
    const tabs = document.querySelectorAll('.tabcontent');
    tabs.forEach(t => t.classList.remove('active'));

    // Mostra a aba alvo
    const alvo = document.getElementById(nomeAba);
    if (alvo) {
      alvo.classList.add('active');
      // Atualiza hash sem recarregar
      if (location.hash !== '#' + nomeAba) {
        history.replaceState(null, '', '#' + nomeAba);
      }
    }

    // Marca botão ativo (opcional)
    const links = document.querySelectorAll('.tablink');
    links.forEach(l => l.classList.remove('active'));
    if (event && event.currentTarget) {
      event.currentTarget.classList.add('active');
    } else {
      // Se foi abertura automática pelo hash, tenta marcar o botão correspondente
      const btn = document.querySelector(`.tablink[onclick*="'${nomeAba}'"]`);
      if (btn) btn.classList.add('active');
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    const hash = (location.hash || '').replace('#', '');
    if (hash) {
      const alvo = document.getElementById(hash);
      if (alvo) {
        abrirAba(null, hash);
        return; // 🔑 sai daqui e não executa o fallback
      }
    }

    // Só abre a primeira aba se não houver hash válido
    const primeira = document.querySelector('.tabcontent');
    if (primeira) abrirAba(null, primeira.id);
  });


</script>
</body>
</html>