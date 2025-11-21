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

$filtroLoja   = $_GET['loja_id'] ?? '';
$filtroStatus = $_GET['status'] ?? '';
$paginaAtual  = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$porPagina    = 10;
$inicio       = ($paginaAtual - 1) * $porPagina;

// Buscar lojas
$lojas = [];
$resLoja = $conn->query("SELECT id, nome FROM lojas ORDER BY nome");
while ($l = $resLoja->fetch_assoc()) {
  $lojas[$l['id']] = $l['nome'];
}

// Buscar inconformidades
$query = "SELECT i.*, f.nome AS solicitante 
          FROM inconformidades i 
          JOIN funcionarios f ON f.id = i.solicitante_id 
          WHERE i.status != 'Encerrado'";

$params = [];
$types  = '';

if ($filtroLoja) {
  $query .= " AND i.loja_id = ?";
  $params[] = $filtroLoja;
  $types   .= 'i';
}
if ($filtroStatus) {
  $query .= " AND i.status = ?";
  $params[] = $filtroStatus;
  $types   .= 's';
}

$query .= " ORDER BY i.abertura DESC LIMIT ?, ?";
$params[] = $inicio;
$params[] = $porPagina;
$types   .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$inconformidades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Total para paginação
$countQuery = "SELECT COUNT(*) AS total FROM inconformidades WHERE status != 'Encerrado'";
if ($filtroLoja) {
  $countQuery .= " AND loja_id = " . intval($filtroLoja);
}
if ($filtroStatus) {
  $countQuery .= " AND status = '" . $conn->real_escape_string($filtroStatus) . "'";
}
$totalRegistros = $conn->query($countQuery)->fetch_assoc()['total'] ?? 0;
$totalPaginas   = ceil($totalRegistros / $porPagina);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Inconformidade Lojas</title>
  <link rel="stylesheet" href="../css/chamados.css">
</head>
<body>
<div class="container">

  <h2>🏬 Inconformidade Lojas</h2>
  <p>Acompanhe e registre inconformidades direcionadas às unidades.</p>

  <!-- Formulário de filtro -->
  <form method="GET" style="margin-bottom:20px;">
    <label><strong>Loja:</strong></label>
    <select name="loja_id">
      <option value="">Todas</option>
      <?php foreach ($lojas as $id => $nome): ?>
        <option value="<?= $id ?>" <?= $filtroLoja == $id ? 'selected' : '' ?>><?= htmlspecialchars($nome) ?></option>
      <?php endforeach; ?>
    </select>

    <label><strong>Status:</strong></label>
    <select name="status">
      <option value="">Todos</option>
      <option value="Aberto" <?= $filtroStatus === 'Aberto' ? 'selected' : '' ?>>Aberto</option>
      <option value="Aguardando resposta" <?= $filtroStatus === 'Aguardando resposta' ? 'selected' : '' ?>>Aguardando resposta</option>
      <option value="Aguardando avaliação" <?= $filtroStatus === 'Aguardando avaliação' ? 'selected' : '' ?>>Aguardando avaliação</option>
      <option value="Reaberto" <?= $filtroStatus === 'Reaberto' ? 'selected' : '' ?>>Reaberto</option>
    </select>
<br><br>
    <button type="submit" class="btn">🔍 Filtrar</button>
  </form>

  <!-- Tabela de inconformidades -->
  <div class="tabela-container">
    <table>
      <thead>
        <tr>
          <th>Loja</th>
          <th>Título</th>
          <th>Descrição</th>
          <th>Abertura</th>
          <th>Solicitante</th>
          <th>Resposta do gerente</th>
          <th>Status</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($inconformidades)): ?>
          <tr><td colspan="8" style="text-align:center;">Nenhuma inconformidade encontrada.</td></tr>
        <?php else: ?>
          <?php foreach ($inconformidades as $i): ?>
            <?php
              $status = strtolower(trim($i['status'] ?? ''));
              $classeStatus = match ($status) {
                'aberto' => 'status-aberto',
                'aguardando resposta' => 'status-andamento',
                'aguardando avaliação' => 'status-avaliacao',
                'reaberto' => 'status-reaberto',
                'encerrado' => 'status-encerrado',
                default => ''
              };
            ?>
            <tr>
              <td><?= htmlspecialchars($lojas[$i['loja_id']] ?? $i['loja_id']) ?></td>
              <td><?= htmlspecialchars($i['titulo']) ?></td>
              <td><?= nl2br(htmlspecialchars($i['descricao'])) ?></td>
              <td><?= date('d/m/Y H:i', strtotime($i['abertura'])) ?></td>
              <td><?= htmlspecialchars($i['solicitante']) ?></td>
              <td><?= nl2br(htmlspecialchars($i['solucao'] ?? '—')) ?></td>
              <td><span class="<?= $classeStatus ?>"><?= htmlspecialchars($i['status']) ?></span></td>
              <td>
                <?php if (!empty($i['solucao'])): ?>
                  <button class="btn" type="button" onclick="abrirModalResposta(<?= (int)$i['id'] ?>)">Responder</button>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Paginação -->
  <?php if ($totalPaginas > 1): ?>
    <div class="paginacao">
      <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
        <?php
          $link = "?pagina=$i";
          if ($filtroLoja) $link .= "&loja_id=" . urlencode($filtroLoja);
          if ($filtroStatus) $link .= "&status=" . urlencode($filtroStatus);
        ?>
        <a href="<?= $link ?>" class="<?= $i == $paginaAtual ? 'ativo' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

  <div class="botoes-acoes" style="margin: 50px;">
    <a class="btn" href="../modulos/pendencias.php">🏠 Voltar</a>
    <button type="button" class="btn" onclick="abrirModalInconformidade()">➕ Nova</button>
    <a class="btn" href="inconformidades_encerradas.php">📁 Encerradas</a>
  </div>

</div>




<!-- Modal de registro -->
<div id="modalInconformidade" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999;">
  <div style="background:#fff; margin:5% auto; padding:20px; width:500px; border-radius:8px;">
    <h3>➕ Registrar inconformidade</h3>
    <form id="formInconformidade" onsubmit="enviarInconformidade(event)">
            <label><strong>Loja:</strong></label><br>
      <select name="loja_id" required>
        <option value="">— Selecione —</option>
        <?php foreach ($lojas as $id => $nome): ?>
          <option value="<?= $id ?>"><?= htmlspecialchars($nome) ?></option>
        <?php endforeach; ?>
      </select><br><br>

      <label><strong>Título:</strong></label><br>
      <input type="text" name="titulo" required style="width:100%;"><br><br>

      <label><strong>Descrição:</strong></label><br>
      <textarea name="descricao" rows="4" required style="width:100%;"></textarea><br><br>

      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" onclick="fecharModalInconformidade()">Cancelar</button>
        <button type="submit">Confirmar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal de resposta do solicitante -->
<div id="modalResposta" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999;">
  <div style="background:#fff; margin:5% auto; padding:20px; width:500px; border-radius:8px;">
    <h3>✍️ Responder inconformidade</h3>
    <form id="formResposta">
      <input type="hidden" name="id" id="respostaId">

      <div id="campoJustificativa" style="display:none; margin-bottom:10px;">
        <label><strong>Justificativa (obrigatória se houver pendência):</strong></label><br>
        <textarea name="resposta" id="respostaTexto" rows="4" style="width:100%;"></textarea>
      </div>

      <p>Deseja encerrar esta inconformidade?</p>
      <div id="botoesResposta" style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" onclick="fecharModalResposta()">Cancelar</button>
        <button type="button" onclick="enviarRespostaComAcao('encerrar')">✅ Sim, encerrar</button>
        <button type="button" onclick="mostrarJustificativa()">🔄 Ainda há pendência</button>
      </div>
    </form>
  </div>
</div>

<script>
/* ---------- Modal de registro ---------- */
function abrirModalInconformidade() {
  document.getElementById('modalInconformidade').style.display = 'block';
}
function fecharModalInconformidade() {
  document.getElementById('modalInconformidade').style.display = 'none';
}

/* ---------- Modal de resposta do solicitante ---------- */
function abrirModalResposta(id) {
  document.getElementById('respostaId').value = id;
  document.getElementById('respostaTexto').value = '';
  document.getElementById('campoJustificativa').style.display = 'none';
  document.getElementById('modalResposta').style.display = 'block';

  // restaura botões padrão
  document.getElementById('botoesResposta').innerHTML = `
    <button type="button" onclick="fecharModalResposta()">Cancelar</button>
    <button type="button" onclick="enviarRespostaComAcao('encerrar')">✅ Sim, encerrar</button>
    <button type="button" onclick="mostrarJustificativa()">🔄 Ainda há pendência</button>
  `;
}
function fecharModalResposta() {
  document.getElementById('modalResposta').style.display = 'none';
}

/* ---------- Mostrar justificativa e trocar botões ---------- */
function mostrarJustificativa() {
  document.getElementById('campoJustificativa').style.display = 'block';
  document.getElementById('botoesResposta').innerHTML = `
    <button type="button" onclick="fecharModalResposta()">Cancelar</button>
    <button type="button" onclick="enviarRespostaComAcao('reabrir')">🔄 Confirmar reabertura</button>
  `;
}

/* ---------- Envio de nova inconformidade ---------- */
function enviarInconformidade(event) {
  event.preventDefault();
  const form = event.target;
  const dados = new FormData(form);

  fetch('salvar_inconformidades.php', {
    method: 'POST',
    body: dados
  })
  .then(res => res.text())
  .then(msg => {
    alert(msg);
    fecharModalInconformidade();
    location.reload();
  })
  .catch(err => {
    alert('❌ Erro ao salvar inconformidade.');
    console.error(err);
  });
}

/* ---------- Envio de resposta do solicitante ---------- */
function enviarRespostaComAcao(acao) {
  const form = document.getElementById('formResposta');
  const dados = new FormData(form);
  const resposta = (dados.get('resposta') || '').trim();

  if (acao === 'reabrir' && !resposta) {
    alert('Por favor, informe a justificativa para reabrir.');
    return;
  }

  dados.set('acao', acao);

  fetch('inconformidade_resposta_solicitante.php', {
    method: 'POST',
    body: dados
  })
  .then(res => res.text())
  .then(msg => {
    alert(msg);
    fecharModalResposta();
    location.reload();
  })
  .catch(err => {
    alert('❌ Erro ao enviar resposta.');
    console.error(err);
  });
}
</script>

</body>
</html>
