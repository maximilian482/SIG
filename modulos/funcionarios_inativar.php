<?php
session_start();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';
require_once ROOT_PATH . '/dados/conexao.php';

$conn = conectar();

// ===============================
// CONFIGURAÇÕES DO LAYOUT
// ===============================
$titulo   = "Inativar Funcionário";
$cssExtra = "/css/funcionarios_inativar.css";

// ===============================
// VALIDAR PARÂMETROS
// ===============================
$id   = intval($_GET['id'] ?? 0);
$loja = intval($_GET['loja'] ?? 0);

if ($id <= 0 || $loja <= 0) {
    $_SESSION['flash'] = [
        'mensagem' => "❌ Parâmetros inválidos para inativação.",
        'tipo'     => "erro"
    ];
    header("Location: funcionarios.php");
    exit;
}

// ===============================
// BUSCAR FUNCIONÁRIO
// ===============================
$sql = "
  SELECT f.*, l.nome AS nome_loja, c.nome_cargo AS nome_cargo
  FROM funcionarios f
  LEFT JOIN lojas l   ON f.loja_id  = l.id
  LEFT JOIN cargos c  ON f.cargo_id = c.id
  WHERE f.id = ? AND f.loja_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $loja);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['flash'] = [
        'mensagem' => "❌ Funcionário não encontrado.",
        'tipo'     => "erro"
    ];
    header("Location: funcionarios.php");
    exit;
}

$f = $result->fetch_assoc();

// ===============================
// INICIAR CAPTURA DO HTML
// ===============================
ob_start();
?>

<h2 class="mb-4">🗑️ Inativar Funcionário</h2>

<div class="card p-3 mb-3">
  <p><strong>Nome:</strong> <?= htmlspecialchars($f['nome']) ?></p>
  <p><strong>Cargo:</strong> <?= htmlspecialchars($f['nome_cargo'] ?? '—') ?></p>
  <p><strong>Loja:</strong> <?= htmlspecialchars($f['nome_loja'] ?? '—') ?></p>
  <p><strong>CPF:</strong> <?= htmlspecialchars($f['cpf']) ?></p>
</div>

<form method="POST" action="funcionarios_salvar_inativacao.php" class="card p-3">
  <input type="hidden" name="id"   value="<?= $f['id'] ?>">
  <input type="hidden" name="loja" value="<?= $f['loja_id'] ?>">

  <div class="mb-3">
    <label class="form-label">Data de desligamento:</label>
    <input type="date" name="desligamento" class="form-control" required>
  </div>

  <div class="mb-3">
    <p class="text-danger">
      ⚠ Esta ação irá marcar o funcionário como <strong>inativo</strong>.
    </p>
  </div>

  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-danger">Confirmar inativação</button>
    <a href="funcionarios.php" class="btn btn-secondary">Cancelar</a>
  </div>
</form>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
