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
$mesAtual = date('m');
$anoAtual = date('Y');

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

// Função para contar reconhecimentos do mês por tipo
function contarReconhecimentos($conn, $funcionarioId, $ano, $mes, $tipo) {
  $sql = "SELECT COUNT(*) AS total 
          FROM reconhecimentos 
          WHERE funcionario_id = ? AND ano = ? AND mes = ? AND tipo = ?";
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
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Comunidade</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
    h2 { color: #333; margin-bottom: 20px; }
    .quadro { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 30px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
    .quadro h3 { margin-top: 0; color: #444; border-bottom: 2px solid #eee; padding-bottom: 10px; }
    .lista { display: flex; flex-wrap: wrap; gap: 20px; }
    .card { background: #fafafa; border: 1px solid #ddd; border-radius: 6px; padding: 15px; width: 220px; text-align: center; }
    .card img { width: 80px; height: 80px; border-radius: 50%; margin-bottom: 10px; object-fit: cover; }
    .card strong { display: block; margin-bottom: 5px; color: #333; }
    .card span { font-size: 14px; color: #666; }
    .card p { font-size: 13px; color: #555; margin-top: 8px; }
    .parabens { margin-top: 10px; background: #28a745; color: #fff; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
    .parabens:hover { background: #218838; }
    @keyframes pulse {
      0% { transform: scale(1); color: #28a745; }
      50% { transform: scale(1.3); color: #28a745; }
      100% { transform: scale(1); color: inherit; }
    }
    .contador.pulsar {
      animation: pulse 0.8s ease;
    }
  </style>
</head>
<body>

<h2>🌟 Comunidade</h2>

<div class="quadro">
  <h3>🎂 Aniversariantes do Mês</h3>
  <div class="lista">
    <?php foreach ($aniversariantes as $f): 
      $contador = contarReconhecimentos($conn, $f['id'], $anoAtual, $mesAtual, 'aniversario');
      $foto = caminhoFoto($f['foto']);
    ?>
      <div class="card">
        <img src="<?= htmlspecialchars($foto) ?>" alt="Foto do colaborador">
        <strong><?= htmlspecialchars($f['nome']) ?></strong>
        <span><?= htmlspecialchars($f['nome_cargo']) ?></span><br>
        <span>🎂 <?= date('d/m', strtotime($f['nascimento'])) ?></span><br>
        <?php if (!empty($f['sobre_mim'])): ?>
          <p>"<?= htmlspecialchars($f['sobre_mim']) ?>"</p>
        <?php endif; ?>
        <span class="contador">🎉 <?= $contador ?> reconhecimentos de aniversário</span><br>
        <button class="parabens" onclick="reconhecerFuncionario(<?= $f['id'] ?>, 'aniversario')">Dar os parabéns 🎉</button>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="quadro">
  <h3>🏆 Tempo de Empresa</h3>
  <div class="lista">
    <?php foreach ($tempoEmpresa as $f): 
      $contador = contarReconhecimentos($conn, $f['id'], $anoAtual, $mesAtual, 'tempo_empresa');
      $anosEmpresa = (date('Y') - date('Y', strtotime($f['contratacao'])));
      $foto = caminhoFoto($f['foto']);
    ?>
      <div class="card">
        <img src="<?= htmlspecialchars($foto) ?>" alt="Foto do colaborador">
        <strong><?= htmlspecialchars($f['nome']) ?></strong>
        <span><?= htmlspecialchars($f['nome_cargo']) ?></span><br>
        <span>🏆 <?= $anosEmpresa ?> anos de empresa</span><br>
        <?php if (!empty($f['sobre_mim'])): ?>
          <p>"<?= htmlspecialchars($f['sobre_mim']) ?>"</p>
        <?php endif; ?>
        <span class="contador">👏 <?= $contador ?> reconhecimentos de tempo de empresa</span><br>
        <button class="parabens" onclick="reconhecerFuncionario(<?= $f['id'] ?>, 'tempo_empresa')">Reconhecer 👏</button>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
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
</script>

</body>
</html>
