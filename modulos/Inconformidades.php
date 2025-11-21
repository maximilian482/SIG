<?php
session_start();
require_once '../dados/conexao.php';
date_default_timezone_set('America/Sao_Paulo');

// Verifica se está logado
if (!isset($_SESSION['usuario'])) {
  header('Location: ../index.php');
  exit;
}

$cpf         = $_SESSION['cpf'] ?? '';
$usuario     = $_SESSION['usuario'];
$lojaId      = $_SESSION['loja'] ?? null;
$idFuncionario = $_SESSION['id_funcionario'] ?? 0;
$cargo       = strtolower($_SESSION['cargo'] ?? '');

// Permitir acesso se for gerente ou tiver permissão para inconformidades
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

if (!$lojaId || ($cargo !== 'gerente' && !temAcesso($conn, $cpf, 'inconformidades'))) {
  header('Location: ../index.php');
  exit;
}

// Buscar inconformidades da loja que não estão encerradas
$stmt = $conn->prepare("
  SELECT i.*, f.nome AS solicitante
  FROM inconformidades i
  JOIN funcionarios f ON f.id = i.solicitante_id
  WHERE i.loja_id = ? AND i.status != 'Encerrado'
  ORDER BY i.abertura DESC
");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$inconformidades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>🏬 Inconformidades da Loja</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 8px; border-bottom: 1px solid #ccc; text-align: left; }
    th { background-color: #f2f2f2; }
    .btn { padding: 6px 12px; background: #007bff; color: #fff; border-radius: 4px; text-decoration: none; }
    .btn:hover { background: #0056b3; }
    .status { padding: 4px 8px; border-radius: 4px; font-weight: bold; }
    .aberto { background:#fff3cd; color:#856404; }
    .tratamento { background:#cce5ff; color:#004085; }
    .avaliacao { background:#ffeeba; color:#856404; }
    .reaberto { background:#f8d7da; color:#721c24; }
  </style>
</head>
<body>

<h2>🏬 Inconformidades da Loja</h2>
<p>Chamados registrados pela sua loja que precisam ser tratados.</p>

<table>
  <tr>
    <th>Título</th>
    <th>Descrição</th>
    <th>Solicitante</th>
    <th>Abertura</th>
    <th>Tempo aberto</th>
    <th>Status</th>
    <th>Ações</th>
  </tr>

  <?php if (empty($inconformidades)): ?>
    <tr><td colspan="7" style="text-align:center;">Nenhuma inconformidade registrada para sua loja.</td></tr>
  <?php else: ?>
    <?php foreach ($inconformidades as $i): ?>
      <?php
        $aberturaTs = strtotime($i['abertura']);
        $tempoAberto = '—';
        if ($aberturaTs) {
          $diff = time() - $aberturaTs;
          $dias = floor($diff / 86400);
          $horas = floor(($diff % 86400) / 3600);
          $min = floor(($diff % 3600) / 60);
          $tempoAberto = $dias > 0 ? "{$dias}d {$horas}h" : ($horas > 0 ? "{$horas}h {$min}m" : "{$min}m");
        }

        $status = strtolower($i['status']);
        $classe = match($status) {
          'aberto' => 'aberto',
          'em tratamento' => 'tratamento',
          'aguardando avaliação' => 'avaliacao',
          'reaberto' => 'reaberto',
          default => '',
        };
      ?>
      <tr>
        <td><?= htmlspecialchars($i['titulo']) ?></td>
        <td><?= nl2br(htmlspecialchars($i['descricao'])) ?></td>
        <td><?= htmlspecialchars($i['solicitante']) ?></td>
        <td><?= date('d/m/Y H:i', $aberturaTs) ?></td>
        <td><?= $tempoAberto ?></td>
        <td><span class="status <?= $classe ?>"><?= htmlspecialchars($i['status']) ?></span></td>
        <td>
          <?php if (in_array($status, ['aberto', 'reaberto'])): ?>
            <button class="btn" onclick="abrirModalTratamento('<?= $i['id'] ?>')">🛠️ Tratar</button>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php endif; ?>
</table>

<a class="btn" href="../index.php" style="margin-top:20px; display:inline-block;">🔙 Voltar ao painel</a>

<!-- Modal de tratamento -->
<div id="modalTratamento" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999;">
  <div style="background:#fff; margin:5% auto; padding:20px; width:500px; border-radius:8px;">
    <h3>🛠️ Tratar inconformidade</h3>
    <form id="formTratamento" onsubmit="enviarTratamento(event)">
      <input type="hidden" name="id" id="tratamentoId">
      <label><strong>Resposta / Solução:</strong></label><br>
      <textarea name="solucao" rows="4" required style="width:100%;"></textarea><br><br>
      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" onclick="fecharModalTratamento()">Cancelar</button>
        <button type="submit">Confirmar</button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirModalTratamento(id) {
  document.getElementById('tratamentoId').value = id;
  document.getElementById('modalTratamento').style.display = 'block';
}
function fecharModalTratamento() {
  document.getElementById('modalTratamento').style.display = 'none';
}
function enviarTratamento(event) {
  event.preventDefault();
  const form = event.target;
  const dados = new FormData(form);

  fetch('salvar_tratamento_inconformidades.php', {
    method: 'POST',
    body: dados
  }).then(res => res.text()).then(msg => {
    alert(msg);
    fecharModalTratamento();
    location.reload();
  });
}
</script>

</body>
</html>
