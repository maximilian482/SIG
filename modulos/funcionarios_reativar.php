<?php
session_start();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';
require_once ROOT_PATH . '/dados/conexao.php';

$conn = conectar();

// ===============================
// CONFIGURAÇÕES DO LAYOUT
// ===============================
$titulo   = "Reativar Funcionário";
$cssExtra = "/css/funcionarios_reativar.css";

// ===============================
// VALIDAR PARÂMETROS
// ===============================
$id   = intval($_GET['id'] ?? 0);
$loja = intval($_GET['loja'] ?? 0);

if ($id <= 0 || $loja <= 0) {
    $_SESSION['flash'] = [
        'mensagem' => "❌ Parâmetros inválidos para reativação.",
        'tipo'     => "erro"
    ];
    header("Location: funcionarios_inativos.php");
    exit;
}

// ===============================
// BUSCAR FUNCIONÁRIO INATIVO
// ===============================
$sql = "
  SELECT f.*, 
         l.nome AS nome_loja,
         c.nome_cargo AS nome_cargo,
         s.nome AS nome_setor
  FROM funcionarios f
  LEFT JOIN lojas   l ON f.loja_id  = l.id
  LEFT JOIN cargos  c ON f.cargo_id = c.id
  LEFT JOIN setores s ON f.id_setor = s.id
  WHERE f.id = ? AND f.loja_id = ? AND f.desligamento IS NOT NULL
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $loja);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['flash'] = [
        'mensagem' => "❌ Funcionário inativo não encontrado.",
        'tipo'     => "erro"
    ];
    header("Location: funcionarios_inativos.php");
    exit;
}

$f = $result->fetch_assoc();

// ===============================
// CARREGAR CARGOS, LOJAS, SETORES
// ===============================
$cargos = [];
$resCargos = $conn->query("SELECT id, nome_cargo FROM cargos ORDER BY nome_cargo");
while ($row = $resCargos->fetch_assoc()) {
    $cargos[$row['id']] = $row['nome_cargo'];
}

$lojas = [];
$resLojas = $conn->query("SELECT id, nome FROM lojas ORDER BY nome");
while ($row = $resLojas->fetch_assoc()) {
    $lojas[$row['id']] = $row['nome'];
}

$setores = [];
$resSetores = $conn->query("SELECT id, nome FROM setores ORDER BY nome");
while ($row = $resSetores->fetch_assoc()) {
    $setores[$row['id']] = $row['nome'];
}

// ===============================
// INICIAR HTML
// ===============================
ob_start();
?>

<h2 class="mb-4">♻️ Reativar Funcionário</h2>

<div class="card p-3 mb-3">
  <p><strong>Nome:</strong> <?= htmlspecialchars($f['nome']) ?></p>
  <p><strong>CPF:</strong> <?= htmlspecialchars($f['cpf']) ?></p>
  <p><strong>Cargo anterior:</strong> <?= htmlspecialchars($f['nome_cargo'] ?? '—') ?></p>
  <p><strong>Loja anterior:</strong> <?= htmlspecialchars($f['nome_loja'] ?? '—') ?></p>
  <p><strong>Setor anterior:</strong> <?= htmlspecialchars($f['nome_setor'] ?? '—') ?></p>
  <p><strong>Data de desligamento:</strong> <?= htmlspecialchars($f['desligamento'] ?? '—') ?></p>
</div>

<form method="POST" action="funcionarios_salvar_reativacao.php" class="card p-3">

  <input type="hidden" name="loja_original" value="<?= $f['loja_id'] ?>">
  <input type="hidden" name="id" value="<?= $f['id'] ?>">

  <div class="mb-3">
    <label class="form-label">Nome:</label>
    <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($f['nome']) ?>" required>
  </div>

  <div class="mb-3">
    <label class="form-label">CPF:</label>
    <input type="text" name="cpf" class="form-control" value="<?= htmlspecialchars($f['cpf']) ?>" readonly>
  </div>

  <div class="mb-3">
    <label class="form-label">Código Vetor:</label>
    <input type="text" name="codigo" class="form-control" value="<?= htmlspecialchars($f['codigo'] ?? '0') ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Código CC:</label>
    <input type="text" name="cc" class="form-control" value="<?= htmlspecialchars($f['cc'] ?? '0') ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Cargo:</label>
    <select name="cargo_id" class="form-select" required>
      <option value="">Selecione</option>
      <?php foreach ($cargos as $idCargo => $nomeCargo): ?>
        <option value="<?= $idCargo ?>" <?= $idCargo == $f['cargo_id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($nomeCargo) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Loja:</label>
    <select name="loja_id" class="form-select" required>
      <option value="">Selecione</option>
      <?php foreach ($lojas as $idLoja => $nomeLoja): ?>
        <option value="<?= $idLoja ?>" <?= $idLoja == $f['loja_id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($nomeLoja) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Setor:</label>
    <select name="setor_id" class="form-select" required>
      <option value="">Selecione</option>
      <?php foreach ($setores as $idSetor => $nomeSetor): ?>
        <option value="<?= $idSetor ?>" <?= $idSetor == $f['id_setor'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($nomeSetor) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Nova data de contratação:</label>
    <input type="date" name="contratacao" class="form-control" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Telefone:</label>
    <input type="text" name="telefone" class="form-control" value="<?= htmlspecialchars($f['telefone'] ?? '') ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Email:</label>
    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($f['email'] ?? '') ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Endereço:</label>
    <input type="text" name="endereco" class="form-control" value="<?= htmlspecialchars($f['endereco'] ?? '') ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Aniversário:</label>
    <input type="date" name="aniversario" class="form-control" value="<?= htmlspecialchars($f['nascimento'] ?? '') ?>">
  </div>

  <div class="mb-3 text-danger">
    ⚠ Esta ação irá reativar o funcionário e removê-lo da lista de inativos.
  </div>

  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-success">Confirmar reativação</button>
    <a href="funcionarios_inativos.php" class="btn btn-secondary">Cancelar</a>
  </div>

</form>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
