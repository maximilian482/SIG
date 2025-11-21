<?php
session_start();
require_once __DIR__ . '/../dados/conexao.php';
$conn = conectar();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['id_funcionario'])) {
  header('Location: ../login.php');
  exit;
}

$id = $_SESSION['id_funcionario'];

// Buscar dados do funcionário
$stmt = $conn->prepare("
  SELECT f.codigo, f.nome, f.email, f.telefone, f.endereco, f.sobre_mim, f.foto, 
         f.contratacao, f.nascimento, f.cpf, 
         c.nome_cargo AS cargo,
         c.descricao AS cargo_descricao,
         l.nome AS loja_nome,
         l.endereco AS loja_endereco
  FROM funcionarios f
  LEFT JOIN cargos c ON f.cargo_id = c.id
  LEFT JOIN lojas l ON f.loja_id = l.id
  WHERE f.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

// Calcular tempo de empresa
$tempoEmpresa = '';
if (!empty($usuario['contratacao'])) {
  $dataContratacao = new DateTime($usuario['contratacao']);
  $hoje = new DateTime();
  $diff = $dataContratacao->diff($hoje);
  $anos = $diff->y;
  $meses = $diff->m;
  if ($anos > 0 && $meses > 0) {
    $tempoEmpresa = " ({$anos} ano(s) e {$meses} mês(es))";
  } elseif ($anos > 0) {
    $tempoEmpresa = " ({$anos} ano(s))";
  } elseif ($meses > 0) {
    $tempoEmpresa = " ({$meses} mês(es))";
  } else {
    $tempoEmpresa = " (menos de 1 mês)";
  }
}

// Caminho da foto
$foto = trim($usuario['foto'] ?? '');
$caminhoFoto = ($foto === '' || !file_exists(__DIR__ . "/../uploads/$foto"))
  ? "../imagens/perfil.png"
  : "../uploads/$foto";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Meu Perfil</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/perfil.css">
</head>
<body>

<?php if (isset($_SESSION['sucesso_foto'])): ?>
  <div style="
    max-width:600px;
    margin:20px auto;
    padding:15px;
    background:#e6ffed;
    border:2px solid #28a745;
    border-radius:8px;
    color:#155724;
    font-size:1.1em;
    font-weight:bold;
    text-align:center;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;
  ">
    <i class="fas fa-check-circle" style="color:#28a745; font-size:1.5em;"></i>
    <?= htmlspecialchars($_SESSION['sucesso_foto']) ?>
  </div>
  <?php unset($_SESSION['sucesso_foto']); ?>
<?php endif; ?>


<?php if (isset($_SESSION['sucesso_foto'])): ?>
  <div class="mensagem-sucesso"><?= htmlspecialchars($_SESSION['sucesso_foto']) ?></div>
  <?php unset($_SESSION['sucesso_foto']); ?>
<?php endif; ?>

<div class="perfil-container">
  <div style="text-align:center;">
    <img src="<?= htmlspecialchars($caminhoFoto) ?>" class="foto" alt="Foto de perfil" onclick="abrirModalFoto()" style="cursor:pointer;">
    <div class="nome-usuario"><?= htmlspecialchars($usuario['nome'] ?? '') ?></div>
  </div>

  <div class="info"><strong>Código Vetor:</strong> <?= htmlspecialchars($usuario['codigo'] ?? '') ?></div>
  <div class="info"><strong>Cargo:</strong> <?= htmlspecialchars($usuario['cargo'] ?? 'Não definido') ?>
    <?php if (!empty($usuario['cargo_descricao'])): ?>
      (<?= htmlspecialchars($usuario['cargo_descricao']) ?>)
    <?php endif; ?>
  </div>
  <div class="info"><strong>Loja:</strong> <?= htmlspecialchars($usuario['loja_nome'] ?? 'Não definida') ?>
    <?php if (!empty($usuario['loja_endereco'])): ?>
      (<?= htmlspecialchars($usuario['loja_endereco']) ?>)
    <?php endif; ?>
  </div>
  <div class="info"><strong>Data de contratação:</strong> <?= $usuario['contratacao'] ? date('d/m/Y', strtotime($usuario['contratacao'])) : '-' ?><?= $tempoEmpresa ?></div>
  <div class="info"><strong>Aniversário:</strong> <?= $usuario['nascimento'] ? date('d/m/Y', strtotime($usuario['nascimento'])) : '-' ?></div>

  <div id="viewMode">
    <div class="info"><strong>Email:</strong> <?= htmlspecialchars($usuario['email'] ?? '') ?></div>
    <div class="info"><strong>Telefone:</strong> <?= htmlspecialchars($usuario['telefone'] ?? '') ?></div>
    <div class="info"><strong>Endereço:</strong> <?= htmlspecialchars($usuario['endereco'] ?? '') ?></div>
    <div class="info"><strong>Sobre mim:</strong> <?= nl2br(htmlspecialchars($usuario['sobre_mim'] ?? '')) ?></div>

    <div class="botoes">
      <button type="button" onclick="ativarEdicao()">✏️ Editar</button>
      <button type="button" onclick="abrirModalSenha()">🔑 Alterar Senha</button>
      <button onclick="history.back()">🔙 Voltar</button>
    </div>
  </div>

  <form id="editMode" method="POST" action="salvar_perfil.php" enctype="multipart/form-data" style="display:none;" onsubmit="return validarFormulario()">
    <label>Email:</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required><br><br>

    <label>Telefone:</label><br>
    <input type="text" name="telefone" value="<?= htmlspecialchars($usuario['telefone'] ?? '') ?>" required><br><br>

    <label>Endereço:</label><br>
    <input type="text" name="endereco" value="<?= htmlspecialchars($usuario['endereco'] ?? '') ?>" required><br><br>

    <label>Sobre mim:</label><br>
    <textarea name="sobre_mim" rows="4"><?= htmlspecialchars($usuario['sobre_mim'] ?? '') ?></textarea><br><br>

    <div class="botoes">
      <button type="submit">💾 Salvar</button>
      <button type="button" onclick="cancelarEdicao()">❌ Cancelar</button>
    </div>
  </form>
</div>

<!-- Modal de senha -->
<div id="modalSenha" class="modal">
  <div class="modal-content">
    <span class="close" onclick="fecharModalSenha()">&times;</span>
    <div class="modal-header">Alterar Senha</div>
    <form method="POST" action="alterar_senha.php" onsubmit="return validarSenha()">
      <label>Senha atual:</label><br>
      <input type="password" name="senha_atual" required><br><br>

      <label>Nova senha:</label><br>
      <input type="password" name="nova_senha" required><br>

      <label>Confirmar nova senha:</label><br>
      <input type="password" name="confirmar_senha" required><br><br>

      <div style="text-align:right;">
        <button type="submit">💾 Salvar Senha</button>
        <button type="button" onclick="fecharModalSenha()">❌ Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal de foto -->
<div id="modalFoto" class="modal">
  <div class="modal-content" style="text-align:center; max-width:500px;">
    <span class="close" onclick="fecharModalFoto()">&times;</span>
    <img src="<?= htmlspecialchars($caminhoFoto) ?>" alt="Foto grande" style="max-width:100%; border-radius:8px; margin-bottom:15px;">
    <form method="POST" action="alterar_foto.php" enctype="multipart/form-data">
      <input type="file" name="nova_foto" id="nova_foto" accept="image/*" required><br><br>
      <button type="submit">📷 Alterar Foto</button>
    </form>
  </div>
</div>

<script src="../js/perfil.js"></script>
</body>
</html>
