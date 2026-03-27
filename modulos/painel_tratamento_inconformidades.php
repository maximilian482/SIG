<?php
session_start();
require_once '../dados/conexao.php';
date_default_timezone_set('America/Sao_Paulo');

// cria a conexão
$conn = conectar(); // ou, se conexao.php já define $conn, não precisa dessa linha

$cpf     = $_SESSION['cpf'] ?? '';
$cargo   = strtolower($_SESSION['cargo'] ?? '');
$lojaId  = $_SESSION['loja'] ?? 0;
$usuario = $_SESSION['usuario'] ?? '';
$idFuncionario = $_SESSION['id_funcionario'] ?? 0;

if (!isset($_SESSION['usuario'])) {
  header('Location: ../index.php');
  exit;
}


// Função de verificação de acesso
function temAcesso($conn, $cpf, $modulo) {
  $stmt = $conn->prepare("
    SELECT 1
    FROM acessos_usuarios au
    JOIN funcionarios f ON au.funcionario_id = f.id
    WHERE f.cpf = ? AND au.$modulo = 1
    LIMIT 1
  ");
  $stmt->bind_param("s", $cpf);
  $stmt->execute();
  return $stmt->get_result()->num_rows > 0;
}

// Bloqueia se não for gerente nem tiver permissão explícita
if (!isset($_SESSION['usuario']) || ($cargo !== 'gerente' && !temAcesso($conn, $cpf, 'painel_tratamento'))) {
  header('Location: ../index.php');
  exit;
}

// Buscar inconformidades da loja (somente pendentes de tratamento)
$stmt = $conn->prepare("
  SELECT i.*, f.nome AS solicitante
  FROM inconformidades i
  JOIN funcionarios f ON f.id = i.solicitante_id
  WHERE i.loja_id = ? 
    AND i.status IN ('Aberto','Reaberto', 'Aguardando resposta')
  ORDER BY i.abertura ASC
");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$inconformidades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Painel de Tratamento de Inconformidades</title>
  <link rel="stylesheet" href="../css/chamados.css">
  
</head>
<body>

<h2>🛠️ Painel de Tratamento de Inconformidades</h2>
<p>Visualize e trate as inconformidades da sua loja.</p>

<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>Título</th>
      <th>Descrição</th>
      <th>Solicitante</th>
      <th>Abertura</th>
      <th>Status</th>
      <th>Ações</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($inconformidades)): ?>
      <tr><td colspan="7" style="text-align:center;">Nenhuma inconformidade encontrada.</td></tr>
    <?php else: ?>
      <?php foreach ($inconformidades as $i): ?>
        <?php
          $status = $i['status'];
          $classe = match(strtolower($status)) {
            'aberto' => 'aberto',
            'aguardando resposta' => 'aguardando',
            'aguardando avaliação' => 'avaliacao',
            'reaberto' => 'reaberto',
            'encerrado' => 'encerrado',
            default => '',
          };
        ?>
        <tr>
          <td><?= htmlspecialchars($i['id']) ?></td>
          <td><?= htmlspecialchars($i['titulo']) ?></td>
          <td>
            <?= nl2br(htmlspecialchars($i['descricao'])) ?>
            <?php if (!empty($i['avaliacao_justificativa'])): ?>
              <div class="justificativa-reaberto">
                🔄 <?= nl2br(htmlspecialchars($i['avaliacao_justificativa'])) ?>
              </div>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($i['solicitante']) ?></td>
          <td><?= date('d/m/Y H:i', strtotime($i['abertura'])) ?></td>
          <td><span class="status <?= $classe ?>"><?= htmlspecialchars($status) ?></span></td>
          <td>
            <?php if (in_array($status, ['Aberto','Reaberto'])): ?>
              <button class="btn" onclick="abrirModalTratamento('<?= $i['id'] ?>')">🛠️ Tratar</button>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<!-- Modal de tratamento -->
<div id="modalTratamento" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999;">
  <div style="background:#fff; margin:5% auto; padding:20px; width:500px; border-radius:8px;">
    <h3>🛠️ Tratar inconformidade</h3>
    <form id="formTratamento" onsubmit="enviarTratamento(event)">
      <input type="hidden" name="id" id="tratamentoId">
      <label><strong>Tratamento (resposta do gerente):</strong></label><br>
      <textarea name="solucao" id="tratamentoTexto" rows="4" required style="width:100%;"></textarea><br><br>
      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" onclick="fecharModalTratamento()">Cancelar</button>
        <button type="submit">Confirmar tratamento</button>
      </div>
    </form>
  </div>
</div>

<div style="margin: 50px;">
  <a class="btn" href="/modulos/pendencias.php" style="margin-top:30px;">🏠 Voltar</a>
  <a class="btn" href="inconformidades_encerradas_loja.php" style="margin-left:10px;">📁 Encerradas</a>
</div>

<script>
function abrirModalTratamento(id) {
  document.getElementById('tratamentoId').value = id;
  document.getElementById('tratamentoTexto').value = '';
  document.getElementById('modalTratamento').style.display = 'block';
}
function fecharModalTratamento() {
  document.getElementById('modalTratamento').style.display = 'none';
}
function enviarTratamento(event) {
  event.preventDefault();
  const id = document.getElementById('tratamentoId').value;
  const tratamento = document.getElementById('tratamentoTexto').value;
  if (!id || !tratamento) {
    alert('Preencha todos os campos.');
    return;
  }
  fetch('inconformidade_resposta_gerente.php', { // backend do gerente
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `id=${encodeURIComponent(id)}&solucao=${encodeURIComponent(tratamento)}`

  })
  .then(response => response.text())
  .then(msg => {
    alert(msg);
    fecharModalTratamento();
    location.reload();
  })
  .catch(error => {
    alert('Erro ao tratar inconformidade.');
    console.error(error);
  });
}
</script>

</body>
</html>
