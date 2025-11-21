<?php
session_start();
if (!isset($_SESSION['usuario'])) {
header('Location: ../login.php');
exit;
}

$usuario = $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Abrir Chamado</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>

<h2>📨 Abrir Chamado</h2>
<p>Preencha os dados abaixo para registrar um novo chamado.</p>

<?php if (isset($_GET['sucesso'])): ?>
<div style="background:#d4edda; color:#155724; padding:10px; border-radius:5px; margin-bottom:20px;">
<?= htmlspecialchars($_GET['sucesso']) ?>
</div>
<?php endif; ?>


<form method="POST" action="salvar_chamado.php" onsubmit="return validarChamado()">
<div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; max-width:720px;">

<div style="grid-column:1 / -1;">
<label><strong>Título do chamado:</strong></label>
<input type="text" name="titulo" id="campoTitulo" required style="width:100%; padding:8px;">
</div>

<div>
<label><strong>Direcionar para (setor):</strong></label>
<select name="cargo" id="campoCargo" required>
<option value="">— Selecione —</option>
<option value="TI">TI</option>
<option value="Manutencao">Manutenção</option>
<option value="Supervisao">Supervisão</option>
</select>
</div>

<div style="grid-column:1 / -1;">
<label><strong>Descrição do problema:</strong></label>
<textarea name="descricao" id="campoDescricao" rows="4" required style="width:100%; padding:8px;"></textarea>
</div>
</div>

<input type="hidden" name="abertura" value="<?= date('Y-m-d H:i:s') ?>">

<div style="margin-top:16px;">
<button type="submit">📨 Registrar chamado</button>
<a class="btn" href="../index.php">🔙 Voltar</a>
</div>
</form>

<script>
function validarChamado() {
const titulo = document.getElementById('campoTitulo').value.trim();
const descricao = document.getElementById('campoDescricao').value.trim();
const setor = document.getElementById('campoCargo').value;

const setoresValidos = ['TI', 'Manutencao', 'Supervisao'];

if (!titulo || !descricao || !setor) {
alert('Preencha todos os campos obrigatórios.');
return false;
}

if (!setoresValidos.includes(setor)) {
alert('Selecione um setor válido: TI, Manutenção ou Supervisão.');
return false;
}

return true;
}
</script>

</body>
</html>

