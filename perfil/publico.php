<?php
session_start();
require_once __DIR__ . '/../dados/conexao.php';

$conn = conectar();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
  exit("Funcionário não encontrado.");
}

$stmt = $conn->prepare("
  SELECT f.id, f.nome, f.sobre_mim, f.contratacao, f.nascimento, f.foto,
         c.nome_cargo, l.nome AS nome_loja
  FROM funcionarios f
  LEFT JOIN cargos c ON c.id = f.cargo_id
  LEFT JOIN lojas l ON l.id = f.loja_id
  WHERE f.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
  exit("Funcionário não encontrado.");
}

// calcula tempo de empresa
$tempoEmpresa = null;
if (!empty($res['contratacao'])) {
  $contratacao = new DateTime($res['contratacao']);
  $hoje = new DateTime();
  $intervalo = $contratacao->diff($hoje);
  $tempoEmpresa = $intervalo->y . " anos e " . $intervalo->m . " meses";
}

// monta caminho da foto
$caminhoFoto = "/imagens/perfil.png";
if (!empty($res['foto'])) {
  $foto = $res['foto'];
  if (strpos($foto, '/uploads/') === 0) {
    $caminhoFoto = $foto;
  } else {
    $caminhoFoto = "/uploads/" . $foto;
  }
  $caminhoAbsoluto = $_SERVER['DOCUMENT_ROOT'] . $caminhoFoto;
  if (!file_exists($caminhoAbsoluto)) {
    $caminhoFoto = "/imagens/perfil.png";
  }
}

// lógica para saber se é aniversário ou tempo de empresa
$hoje = new DateTime();
$mostrarAniversario = (date('m', strtotime($res['nascimento'])) == $hoje->format('m'));
$mostrarTempoEmpresa = (!empty($res['contratacao']) && date('m', strtotime($res['contratacao'])) == $hoje->format('m'));
?>
<div style="text-align:center;">
  <img src="<?= htmlspecialchars($caminhoFoto) ?>" 
       alt="Foto do colaborador" 
       style="width:200px;height:200px;border-radius:50%;object-fit:cover;margin-bottom:15px;box-shadow:0 2px 6px rgba(0,0,0,0.2);">
</div>
<h3><?= htmlspecialchars($res['nome']) ?></h3>
<!-- <p><strong>Cargoo:</strong> <?= htmlspecialchars($res['nome_cargo']) ?></p> -->
<p><strong>Loja:</strong> <?= htmlspecialchars($res['nome_loja']) ?></p>
<p><strong>Tempo de empresa:</strong> <?= $tempoEmpresa ?></p>
<p><strong>Aniversário:</strong> <?= date('d/m', strtotime($res['nascimento'])) ?></p>
<?php if (!empty($res['sobre_mim'])): ?>
  <p><strong>Sobre mim:</strong> <?= nl2br(htmlspecialchars($res['sobre_mim'])) ?></p>
<?php endif; ?>

<div style="margin-top:20px; text-align:center;">
  <?php if ($mostrarAniversario): ?>
    <button onclick="reconhecerFuncionario(<?= $id ?>, 'aniversario')" 
            style="background:#28a745;color:#fff;border:none;padding:8px 14px;border-radius:4px;cursor:pointer;">
      Dar os parabéns 🎉
    </button>
  <?php endif; ?>

  <?php if ($mostrarTempoEmpresa): ?>
    <button onclick="reconhecerFuncionario(<?= $id ?>, 'tempo_empresa')" 
            style="background:#007bff;color:#fff;border:none;padding:8px 14px;border-radius:4px;cursor:pointer;">
      Reconhecer tempo de empresa 👏
    </button>
  <?php endif; ?>



  

