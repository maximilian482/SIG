<?php
session_start();
require_once '../includes/funcoes.php';
$conn = conectar();

// Se vier via POST (login)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    $sql = "SELECT id, nome, senha FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $usuario = $res->fetch_assoc();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];

        header("Location: ../modulos/comunidade.php");
        exit;
    } else {
        echo "Login inválido.";
    }
}

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
  WHERE MONTH(f.nascimento) = ? AND f.desligamento IS NULL
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
  WHERE MONTH(f.contratacao) = ? AND f.desligamento IS NULL
";
$stmt = $conn->prepare($sqlTempo);
$stmt->bind_param("i", $mesAtual);
$stmt->execute();
$tempoEmpresa = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Conheça outros colaboradores (não aniversariantes nem tempo de empresa)
$searchNome = $_GET['search'] ?? '';
$like = "%" . $searchNome . "%";

// Paginação
$limite = isset($_GET['limite']) ? max(1, (int)$_GET['limite']) : 10;
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;

// Total de registros
$sqlTotal = "
  SELECT COUNT(*) AS total
  FROM funcionarios f
  WHERE f.desligamento IS NULL
    AND (f.nascimento IS NULL OR MONTH(f.nascimento) <> ?)
    AND (f.contratacao IS NULL OR MONTH(f.contratacao) <> ?)
    AND f.nome LIKE ?
";
$stmt = $conn->prepare($sqlTotal);
$stmt->bind_param("iis", $mesAtual, $mesAtual, $like);
$stmt->execute();
$totalRegistros = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

$totalPaginas = ($totalRegistros > 0) ? ceil($totalRegistros / $limite) : 1;
if ($pagina > $totalPaginas) $pagina = $totalPaginas;
$offset = ($pagina - 1) * $limite;

// Consulta paginada
$sqlOutros = "
  SELECT f.id, f.nome, f.cargo_id, c.nome_cargo, f.sobre_mim, f.foto
  FROM funcionarios f
  LEFT JOIN cargos c ON f.cargo_id = c.id
  WHERE f.desligamento IS NULL
    AND (f.nascimento IS NULL OR MONTH(f.nascimento) <> ?)
    AND (f.contratacao IS NULL OR MONTH(f.contratacao) <> ?)
    AND f.nome LIKE ?
  ORDER BY f.nome ASC
  LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sqlOutros);
$stmt->bind_param("iisii", $mesAtual, $mesAtual, $like, $limite, $offset);
$stmt->execute();
$outrosColaboradores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Totais para exibir nos títulos das abas
$totalAniversariantes = count($aniversariantes);
$totalTempoEmpresa    = count($tempoEmpresa);
$totalOutros          = $totalRegistros;


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

// Função para montar caminho da foto
function caminhoFoto($fotoBanco) {
  $foto = '/imagens/perfil.png'; // padrão
  if (!empty($fotoBanco)) {
    if (strpos($fotoBanco, '/uploads/') === 0) {
      $foto = $fotoBanco;
    } else {
      $foto = '/uploads/' . $fotoBanco;
    }
    $caminhoAbsoluto = $_SERVER['DOCUMENT_ROOT'] . $foto;
    if (!file_exists($caminhoAbsoluto)) {
      $foto = '/imagens/perfil.png';
    }
  }
  return $foto;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/comunidade.css">
  <title>Comunidade</title> 
</head>
<body>

<h2>🌟 Comunidade Souza Farma</h2>

<!-- Navegação das abas -->
<div class="tabs">
  <button class="tablink" onclick="abrirAba(event, 'aniversario')" id="defaultOpen">
    🎂 Aniversariantes (<?= $totalAniversariantes ?>)
  </button>
  <button class="tablink" onclick="abrirAba(event, 'tempo')">
    🏆 Tempo de Empresa (<?= $totalTempoEmpresa ?>)
  </button>
  <button class="tablink" onclick="abrirAba(event, 'colaboradores')">
    👥 Colaboradores (<?= $totalOutros ?>)
  </button>
</div>

<!-- Conteúdo das abas -->
<div id="aniversario" class="tabcontent">
  <h3>🎂 Aniversariantes do Mês</h3>
  <div class="lista">
    <?php foreach ($aniversariantes as $f): 
      $contador = contarReconhecimentos($conn, $f['id'], $anoAtual, $mesAtual, 'aniversario');
      $foto = caminhoFoto($f['foto']);
    ?>
      <div class="card">
        <img src="<?= htmlspecialchars($foto) ?>" alt="Foto"
             onclick="abrirPerfilPublico(<?= $f['id'] ?>)">
        <strong><?= htmlspecialchars($f['nome']) ?></strong>
        <span><?= htmlspecialchars($f['nome_cargo']) ?></span><br>
        <span>🎂 <?= date('d/m', strtotime($f['nascimento'])) ?></span><br>
        <span class="contador">🎉 <?= $contador ?> reconhecimentos</span><br>
        <button class="parabens" onclick="reconhecerFuncionario(<?= $f['id'] ?>, 'aniversario')">
          Dar os parabéns 🎉
        </button>
      </div>
    <?php endforeach; ?>
  </div>
</div>

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
             onclick="abrirPerfilPublico(<?= $f['id'] ?>)">
        <strong><?= htmlspecialchars($f['nome']) ?></strong>
        <span><?= htmlspecialchars($f['nome_cargo']) ?></span><br>
        <span>🏆 <?= $anosEmpresa ?> anos de empresa</span><br>
        <span class="contador">👏 <?= $contador ?> reconhecimentos</span><br>
        <button class="parabens" onclick="reconhecerFuncionario(<?= $f['id'] ?>, 'tempo_empresa')">
          Reconhecer 👏
        </button>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div id="colaboradores" class="tabcontent">
  <h3>👥 Conheça outros colaboradores</h3>

   <form method="GET" style="margin-bottom:15px;">
    <input type="text" name="search" placeholder="Pesquisar por nome..."
           value="<?= htmlspecialchars($searchNome) ?>">
    <!-- preserva aba e limite -->
    <input type="hidden" name="aba" value="colaboradores">
    <input type="hidden" name="limite" value="<?= $limite ?>">
    <button type="submit">🔍 Buscar</button>
  </form>

 <!-- Lista de cards -->
  <div class="lista">
    <?php foreach ($outrosColaboradores as $f): 
      $foto = caminhoFoto($f['foto']);
    ?>
      <div class="card">
        <img src="<?= htmlspecialchars($foto) ?>" alt="Foto"
             onclick="abrirPerfilPublico(<?= $f['id'] ?>)">
        <strong><?= htmlspecialchars($f['nome']) ?></strong>
        <span><?= htmlspecialchars($f['nome_cargo']) ?></span><br>
        <?php if (!empty($f['sobre_mim'])): ?>
          <p>"<?= htmlspecialchars($f['sobre_mim']) ?>"</p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Paginação -->
  <div class="paginacao">
    <?php if ($totalPaginas > 1): ?>
      <?php
        $inicio = max(1, $pagina - 2);
        $fim = min($totalPaginas, $pagina + 2);
      ?>
      <!-- Botão anterior -->
      <?php if ($pagina > 1): ?>
        <a href="?pagina=<?= $pagina-1 ?>&search=<?= urlencode($searchNome) ?>&limite=<?= $limite ?>&aba=colaboradores">&lsaquo; Anterior</a>
      <?php endif; ?>

      <!-- Números limitados -->
      <?php for ($i = $inicio; $i <= $fim; $i++): ?>
        <a href="?pagina=<?= $i ?>&search=<?= urlencode($searchNome) ?>&limite=<?= $limite ?>&aba=colaboradores"
           <?= ($i == $pagina) ? 'class="ativo"' : '' ?>>
          <?= $i ?>
        </a>
      <?php endfor; ?>

      <!-- Botão próximo -->
      <?php if ($pagina < $totalPaginas): ?>
        <a href="?pagina=<?= $pagina+1 ?>&search=<?= urlencode($searchNome) ?>&limite=<?= $limite ?>&aba=colaboradores">Próxima &rsaquo;</a>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
  <!-- Seletor de limite -->
  <form method="GET" style="margin-bottom:15px;">
    <label for="limite">Mostrar:</label>
    <select name="limite" id="limite" onchange="this.form.submit()">
      <option value="10" <?= $limite==10?'selected':'' ?>>10</option>
      <option value="20" <?= $limite==20?'selected':'' ?>>20</option>
      <option value="50" <?= $limite==50?'selected':'' ?>>50</option>
    </select>
    <input type="hidden" name="aba" value="colaboradores">
    <input type="hidden" name="search" value="<?= htmlspecialchars($searchNome) ?>">
  </form>
</div>



<!-- Modal de perfil -->
<div id="perfilModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="fecharPerfilModal()">&times;</span>
    <div id="perfilInfo"></div>
  </div>
</div>

<script>
// Função para reconhecer funcionário (incrementa contador e desabilita botão)
function reconhecerFuncionario(id, tipo) {
  fetch('reconhecer.php?funcionario_id=' + id + '&tipo=' + tipo, {
    credentials: 'same-origin'
  })
  .then(r => r.json())
  .then(data => {
    if (data.sucesso) {
      const botao = document.querySelector(
        `.parabens[onclick="reconhecerFuncionario(${id}, '${tipo}')"]`
      );
      const card = botao.closest('.card');
      const contadorSpan = card.querySelector('.contador');

      let numeroAtual = parseInt(contadorSpan.textContent.match(/\d+/)) || 0;
      numeroAtual++;

      if (tipo === 'aniversario') {
        contadorSpan.textContent = `🎉 ${numeroAtual} reconhecimentos de aniversário`;
      } else if (tipo === 'tempo_empresa') {
        contadorSpan.textContent = `👏 ${numeroAtual} reconhecimentos de tempo de empresa`;
      }

      contadorSpan.classList.add('pulsar');
      setTimeout(() => contadorSpan.classList.remove('pulsar'), 800);

      botao.textContent = "✅ Reconhecido";
      botao.disabled = true;
      botao.style.backgroundColor = "#6c757d";
    } else {
      alert("Erro: " + data.mensagem);
    }
  })
  .catch(err => {
    console.error("Erro de comunicação com o servidor:", err);
    alert("Erro de comunicação com o servidor.");
  });
}

// Funções para abrir/fechar modal de perfil
function abrirPerfilPublico(id) {
  fetch('/perfil/publico.php?id=' + id)
    .then(res => {
      if (!res.ok) throw new Error("Erro ao carregar perfil");
      return res.text();
    })
    .then(html => {
      document.getElementById('perfilInfo').innerHTML = html;
      document.getElementById('perfilModal').style.display = 'block';
    })
    .catch(err => alert(err.message));
}

function fecharPerfilModal() {
  document.getElementById('perfilModal').style.display = 'none';
}
</script>

<script src="/js/abas.js"></script>
<script src="/js/paginacao.js"></script>

</body>
</html>
