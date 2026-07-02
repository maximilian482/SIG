<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

$erros = $_SESSION['erros'] ?? [];
unset($_SESSION['erros']);


require_once __DIR__ . '/../config/bootstrap.php';
include ROOT_PATH . '/includes/funcoes.php';

// ===============================
// CONFIGURAÇÕES DO LAYOUT
// ===============================
$titulo = "Funcionários Inativos";
$cssExtra = "/css/funcionarios_inativos.css";

// ===============================
// FILTROS
// ===============================
$lojaSelecionada  = $_GET['loja'] ?? '';
$cargoSelecionado = $_GET['cargo'] ?? '';
$paginaAtual      = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$limite           = isset($_GET['limite']) ? max(10, intval($_GET['limite'])) : 10;
$busca            = trim($_GET['busca'] ?? '');
$inicio           = ($paginaAtual - 1) * $limite;

// ===============================
// CARREGAR LOJAS
// ===============================
$lojas = [];
$resLojas = $conn->query("SELECT id, nome FROM lojas ORDER BY nome");
while ($row = $resLojas->fetch_assoc()) {
    $lojas[$row['id']] = $row['nome'];
}

// ===============================
// CARREGAR CARGOS
// ===============================
$cargos = [];
$resCargos = $conn->query("SELECT id, nome_cargo FROM cargos ORDER BY nome_cargo");
while ($row = $resCargos->fetch_assoc()) {
    $cargos[$row['id']] = $row['nome_cargo'];
}

// ===============================
// CARREGAR SETORES
// ===============================
$setores = [];
$resSetores = $conn->query("SELECT id, nome FROM setores ORDER BY nome");
while ($row = $resSetores->fetch_assoc()) {
    $setores[$row['id']] = $row['nome'];
}

// ===============================
// FUNÇÃO PARA MONTAR FILTROS SQL
// ===============================
function montarFiltros(&$sql, &$params, &$types, $loja, $cargo, $busca) {
    $sql .= " WHERE f.desligamento IS NOT NULL";

    if ($loja !== '') {
        $sql .= " AND f.loja_id = ?";
        $params[] = $loja;
        $types .= 'i';
    }

    if ($cargo !== '') {
        $sql .= " AND c.nome_cargo = ?";
        $params[] = $cargo;
        $types .= 's';
    }

    if ($busca !== '') {
        $sql .= " AND (LOWER(f.nome) LIKE ? OR f.codigo LIKE ?)";
        $buscaLike = '%' . strtolower($busca) . '%';
        $params[] = $buscaLike;
        $params[] = $buscaLike;
        $types .= 'ss';
    }
}

// ===============================
// CONSULTA PRINCIPAL
// ===============================
$sql = "
    SELECT f.*, l.nome AS nome_loja, c.nome_cargo AS nome_cargo
    FROM funcionarios f
    LEFT JOIN lojas l ON f.loja_id = l.id
    LEFT JOIN cargos c ON f.cargo_id = c.id
";

$params = [];
$types  = '';
montarFiltros($sql, $params, $types, $lojaSelecionada, $cargoSelecionado, $busca);

$sql .= " ORDER BY f.desligamento DESC LIMIT ?, ?";
$params[] = $inicio;
$params[] = $limite;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$listaFiltrada = [];
while ($f = $result->fetch_assoc()) {

    // Nome reduzido
    $partes = explode(' ', trim($f['nome']));
    $primeiro = $partes[0] ?? '';
    $ultimo   = $partes[count($partes)-1] ?? '';
    $f['nome_reduzido'] = $primeiro . ' ' . $ultimo;

    $listaFiltrada[] = [
        'loja'  => $f['loja_id'],
        'id'    => $f['id'],
        'dados' => $f
    ];
}

// ===============================
// CONTAGEM TOTAL
// ===============================
$sqlTotal = "
    SELECT COUNT(*) AS total
    FROM funcionarios f
    LEFT JOIN cargos c ON f.cargo_id = c.id
";

$paramsTotal = [];
$typesTotal  = '';
montarFiltros($sqlTotal, $paramsTotal, $typesTotal, $lojaSelecionada, $cargoSelecionado, $busca);

if (!empty($typesTotal)) {
    $stmtTotal = $conn->prepare($sqlTotal);
    $stmtTotal->bind_param($typesTotal, ...$paramsTotal);
    $stmtTotal->execute();
    $resultTotal = $stmtTotal->get_result();
} else {
    $resultTotal = $conn->query($sqlTotal);
}

$totalFiltrados = $resultTotal->fetch_assoc()['total'];
$totalPaginas   = ceil($totalFiltrados / $limite);
$listaPaginada  = $listaFiltrada;

// ===============================
// FUNÇÃO TEMPO DE EMPRESA
// ===============================
function tempoDeEmpresa($inicio, $fim) {
    if (!$inicio || !$fim) return '—';
    $inicio = new DateTime($inicio);
    $fim    = new DateTime($fim);
    $intervalo = $inicio->diff($fim);

    $anos = $intervalo->y;
    $meses = $intervalo->m;

    $texto = '';
    if ($anos > 0) $texto .= $anos . ' ano' . ($anos > 1 ? 's' : '');
    if ($meses > 0) {
        if ($texto) $texto .= ' e ';
        $texto .= $meses . ' mes' . ($meses > 1 ? 'es' : '');
    }

    return $texto ?: 'Menos de 1 mes';
}

// ===============================
// INICIAR CAPTURA DO HTML
// ===============================
ob_start();
?>

<h2 class="mb-4">🚫 Funcionários Inativos</h2>

<?php if (isset($_SESSION['flash'])): ?>
  <div class="alert alert-<?= $_SESSION['flash']['tipo'] === 'sucesso' ? 'success' : 'danger' ?>">
    <?= $_SESSION['flash']['mensagem'] ?>
  </div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- ===============================
     FILTROS
=============================== -->
<div class="card p-3 mb-4">
  <form method="GET" class="row g-3">

    <div class="col-md-4">
      <label class="form-label">Loja</label>
      <select name="loja" class="form-select" onchange="this.form.submit()">
        <option value="">Todas</option>
        <?php foreach ($lojas as $id => $nome): ?>
          <option value="<?= $id ?>" <?= (string)$id === (string)$lojaSelecionada ? 'selected' : '' ?>>
            <?= htmlspecialchars($nome) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label">Cargo</label>
      <select name="cargo" class="form-select" onchange="this.form.submit()">
        <option value="">Todos</option>
        <?php foreach ($cargos as $idCargo => $nomeCargo): ?>
          <option value="<?= $nomeCargo ?>" <?= $nomeCargo === $cargoSelecionado ? 'selected' : '' ?>>
            <?= htmlspecialchars($nomeCargo) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label">Pesquisar</label>
      <div class="input-group">
        <input type="text" name="busca" class="form-control" value="<?= htmlspecialchars($busca) ?>" placeholder="Nome ou código">
        <button type="submit" class="btn btn-outline-secondary btn-sm">🔍</button>
      </div>
    </div>

    <div class="col-12 text-end">
      <a href="funcionarios_inativos.php" class="btn btn-secondary">Limpar</a>
    </div>

  </form>

  <div class="mt-3 text-muted">
    <strong>Total de inativos encontrados:</strong> <?= $totalFiltrados ?>
  </div>
</div>

<!-- ===============================
     TABELA
=============================== -->
<div class="card p-3">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>Cód Vetor</th>
          <th>Nome</th>
          <th>Desligamento</th>
          <th>Tempo de empresa</th>
          <th>Ações</th>
        </tr>
      </thead>

      <tbody>
      <?php foreach ($listaPaginada as $item):
        $f = $item['dados'];
      ?>
        <tr>
          <td><?= htmlspecialchars($f['codigo'] ?? '0') ?></td>
          <td><?= htmlspecialchars($f['nome_reduzido']) ?></td>
          <td><?= htmlspecialchars($f['desligamento'] ?? '—') ?></td>
          <td><?= tempoDeEmpresa($f['contratacao'], $f['desligamento']) ?></td>
          <td>
            <a class="btn btn-sm btn-warning"
              href="funcionarios_reativar.php?loja=<?= $f['loja_id'] ?>&id=<?= $f['id'] ?>">
              ♻️ Reativar
            </a>
          </td>

        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<br>
<a href="funcionarios.php" class="btn btn-primary">🔙 Voltar para ativos</a>

<!-- ===============================
     MODAL DE REATIVAÇÃO (COMPATÍVEL COM modal.js)
=============================== -->
<div id="modalReativacao" class="modal-custom">
  <div class="modal-custom-content">

    <h3>♻️ Reativar Funcionário</h3>

    <form method="POST" action="funcionarios_salvar_reativacao.php" id="formReativacao" class="mt-3">

      <input type="hidden" name="loja_original" id="loja_original">
      <input type="hidden" name="id" id="id">

      <div class="mb-2">
        <label class="form-label">Nome:</label>
        <input type="text" name="nome" id="nome" class="form-control" required>
      </div>

      <div class="mb-2">
        <label class="form-label">CPF:</label>
        <input type="text" name="cpf" id="cpf" class="form-control" readonly>
      </div>

      <div class="mb-2">
        <label class="form-label">Código Vetor:</label>
        <input type="text" name="codigo" id="codigo" class="form-control" value="0">
      </div>

      <div class="mb-2">
        <label class="form-label">Código CC:</label>
        <input type="text" name="cc" id="cc" class="form-control" value="0">
      </div>

      <div class="mb-2">
        <label class="form-label">Cargo:</label>
        <select name="cargo_id" id="cargo_id" class="form-select" required>
          <?php foreach ($cargos as $idCargo => $nomeCargo): ?>
            <option value="<?= $idCargo ?>"><?= htmlspecialchars($nomeCargo) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-2">
        <label class="form-label">Loja:</label>
        <select name="loja_id" id="loja_id" class="form-select" required>
          <?php foreach ($lojas as $idLoja => $nomeLoja): ?>
            <option value="<?= $idLoja ?>"><?= htmlspecialchars($nomeLoja) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-2">
        <label class="form-label">Setor:</label>
        <select name="setor_id" id="setor_id" class="form-select" required>
          <?php foreach ($setores as $idSetor => $nomeSetor): ?>
            <option value="<?= $idSetor ?>"><?= htmlspecialchars($nomeSetor) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-2">
        <label class="form-label">Nova data de contratação:</label>
        <input type="date" name="contratacao" id="contratacao" class="form-control" required>
      </div>

      <div class="mb-2">
        <label class="form-label">Telefone:</label>
        <input type="text" name="telefone" id="telefone" class="form-control">
      </div>

      <div class="mb-2">
        <label class="form-label">Email:</label>
        <input type="email" name="email" id="email" class="form-control">
      </div>

      <div class="mb-2">
        <label class="form-label">Endereço:</label>
        <input type="text" name="endereco" id="endereco" class="form-control">
      </div>

      <div class="mb-2">
        <label class="form-label">Aniversário:</label>
        <input type="date" name="aniversario" id="aniversario" class="form-control">
      </div>

      <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-success">Confirmar reativação</button>
        <button type="button" class="btn btn-secondary" onclick="fecharModal('modalReativacao')">Cancelar</button>
      </div>

    </form>

  </div>
</div>

<script>
function abrirModalReativacao(funcionario) {

  document.getElementById('loja_original').value = funcionario.loja_id;
  document.getElementById('id').value = funcionario.id;

  document.getElementById('nome').value = funcionario.nome || '';
  document.getElementById('cpf').value = funcionario.cpf || '';
  document.getElementById('codigo').value = funcionario.codigo || '0';
  document.getElementById('cc').value = funcionario.cc || '0';

  document.getElementById('cargo_id').value = funcionario.cargo_id || '';
  document.getElementById('loja_id').value = funcionario.loja_id || '';
  document.getElementById('setor_id').value = funcionario.id_setor || '';

  const hoje = new Date().toISOString().split('T')[0];
  document.getElementById('contratacao').value = hoje;

  document.getElementById('telefone').value = funcionario.telefone || '';
  document.getElementById('email').value = funcionario.email || '';
  document.getElementById('endereco').value = funcionario.endereco || '';
  document.getElementById('aniversario').value = funcionario.nascimento || '';

  abrirModal('modalReativacao');
}
</script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . "/includes/layout.php";
?>
