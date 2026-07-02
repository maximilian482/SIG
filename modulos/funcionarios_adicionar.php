<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
include ROOT_PATH . '/includes/funcoes.php';

// ===============================
// CONFIGURAÇÕES DO LAYOUT
// ===============================
$titulo = "Adicionar Funcionário";
$cssExtra = "/css/funcionarios_adicionar.css";

// ===============================
// CARREGAR DADOS DA SESSÃO
// ===============================
$dados = $_SESSION['dados_funcionario'] ?? [];

// ===============================
// CARREGAR CARGOS
// ===============================
$cargos = [];
$resCargos = $conn->query("
    SELECT id, nome_cargo 
    FROM cargos 
    WHERE nome_cargo NOT IN ('SUPER', 'CEO')
    ORDER BY nome_cargo
");
while ($row = $resCargos->fetch_assoc()) {
    $cargos[$row['id']] = $row['nome_cargo'];
}

// ===============================
// CARREGAR LOJAS
// ===============================
$lojas = [];
$resLojas = $conn->query("SELECT id, nome FROM lojas ORDER BY nome");
while ($row = $resLojas->fetch_assoc()) {
    $lojas[$row['id']] = $row['nome'];
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
// CARREGAR FUNÇÕES SECUNDÁRIAS
// ===============================
$funcoesSec = [];
$resFuncoes = $conn->query("SELECT id, nome FROM funcoes_secundarias ORDER BY nome");
while ($row = $resFuncoes->fetch_assoc()) {
    $funcoesSec[$row['id']] = $row['nome'];
}

// Buscar ID da loja GERAL
$lojaGeral = $conn->query("SELECT id FROM lojas WHERE nome = 'GERAL' LIMIT 1")->fetch_assoc()['id'];

// Buscar ID do setor GERAL
$setorGeral = $conn->query("SELECT id FROM setores WHERE nome = 'GERAL' LIMIT 1")->fetch_assoc()['id'];

// ===============================
// MAPA CARGO → SETOR
// ===============================
$mapaCargoSetor = [
    1 => 16, 2 => 1, 3 => 13, 4 => 14, 5 => 16, 6 => 3, 7 => 16, 8 => 7,
    9 => 16, 10 => 16, 11 => 16, 12 => 16, 13 => 16, 14 => 15, 15 => 16,
    16 => 16, 17 => 16, 18 => 16, 19 => 7, 30 => 16, 31 => 2, 32 => 6,
    33 => 16, 34 => 8, 46 => 12
];

// ===============================
// INICIAR CAPTURA DO HTML
// ===============================
ob_start();
?>


<h2 class="mb-4">➕ Adicionar novo funcionário</h2>

<form method="POST" action="funcionarios_salvar.php" class="form-funcionario">

  <div class="row g-3">

    <!-- Pequenos -->
    <div class="col-md-4">
      <label class="form-label">Código Manual (Cod Vetor):</label>
      <input type="text" name="codigo" class="form-control"
             value="<?= htmlspecialchars($dados['codigo'] ?? '0') ?>">
    </div>

    <div class="col-md-4">
      <label class="form-label">CC (Contabilidade):</label>
      <input type="text" name="cc" class="form-control"
             value="<?= htmlspecialchars($dados['cc'] ?? '0') ?>">
    </div>

    <div class="col-md-4">
      <label class="form-label">CPF:</label>
      <input type="text" name="cpf" class="form-control"
            value="<?= htmlspecialchars($dados['cpf'] ?? '') ?>" required>
    </div>


    <!-- GRANDES -->
    <div class="col-12">
      <label class="form-label">Nome:</label>
      <input type="text" name="nome" class="form-control"
             value="<?= htmlspecialchars($dados['nome'] ?? '') ?>" required>
    </div>

    <div class="col-12">
      <label class="form-label">Endereço:</label>
      <input type="text" name="endereco" class="form-control"
             value="<?= htmlspecialchars($dados['endereco'] ?? '') ?>">
    </div>

    <!-- Médios -->
    <div class="col-md-6">
      <label class="form-label">Cargo:</label>
      <div class="input-group">
        <select name="cargo_id" id="cargo_id" class="form-select" required>
          <option value="" disabled selected>Selecione</option>
          <?php foreach ($cargos as $id => $cargo): ?>
            <option value="<?= $id ?>"
              <?= isset($dados['cargo_id']) && $dados['cargo_id'] == $id ? 'selected' : '' ?>>
              <?= htmlspecialchars($cargo) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="btn btn-success" onclick="abrirModal('modalCargo')">+</button>
      </div>
    </div>

    <div class="col-md-6">
      <label class="form-label">Setor:</label>
      <div class="input-group">
        <select name="id_setor" id="id_setor" class="form-select" required>
          <option value="" disabled selected>Selecione um setor</option>
          <?php foreach ($setores as $id => $nome): ?>
            <option value="<?= $id ?>"
              <?= isset($dados['id_setor']) && $dados['id_setor'] == $id ? 'selected' : '' ?>>
              <?= htmlspecialchars($nome) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="btn btn-success" onclick="abrirModal('modalSetor')">+</button>
      </div>
    </div>

    <div class="col-md-6">
      <label class="form-label">Loja:</label>
      <select name="loja_id" class="form-select" required>
        <?php foreach ($lojas as $id => $nome): ?>
          <option value="<?= $id ?>"
            <?= isset($dados['loja_id']) && $dados['loja_id'] == $id ? 'selected' : ($id == $lojaGeral ? 'selected' : '') ?>>
            <?= htmlspecialchars($nome) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-6">
      <label class="form-label">Função Secundária:</label>
      <select name="funcao_secundaria_id" class="form-select">
        <option value="0">Nenhuma</option>
        <?php foreach ($funcoesSec as $idFunc => $nomeFunc): ?>
          <option value="<?= $idFunc ?>"
            <?= isset($dados['funcao_secundaria_id']) && $dados['funcao_secundaria_id'] == $idFunc ? 'selected' : '' ?>>
            <?= htmlspecialchars($nomeFunc) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- GRANDES -->
    <div class="col-12">
      <label class="form-label">Email:</label>
      <input type="email" name="email" class="form-control"
             value="<?= htmlspecialchars($dados['email'] ?? '') ?>">
    </div>

    <div class="col-md-6">
      <label class="form-label">Data de contratação:</label>
      <input type="date" name="contratacao" class="form-control"
             value="<?= htmlspecialchars($dados['contratacao'] ?? '') ?>" required>
    </div>

    <div class="col-md-6">
      <label class="form-label">Aniversário:</label>
      <input type="date" name="aniversario" class="form-control"
             value="<?= htmlspecialchars($dados['aniversario'] ?? '') ?>">
    </div>

    <div class="col-12">
      <label class="form-label">Telefone:</label>
      <input type="text" name="telefone" class="form-control"
             placeholder="(99) 99999-9999"
             value="<?= htmlspecialchars($dados['telefone'] ?? '') ?>">
    </div>

    <input type="hidden" name="ativo" value="1">
    <input type="hidden" name="eh_funcionario" value="1">

    <div class="col-12 mt-3 d-flex gap-2">
      <button type="submit" class="btn btn-primary">💾 Salvar</button>
      <a class="btn btn-secondary" href="funcionarios.php">🔙 Voltar</a>
    </div>

  </div>

</form>

<!-- ===============================
     MODAL NOVO CARGO
=============================== -->
<div id="modalCargo" class="modal-custom">
  <div class="modal-custom-content">
    <span class="modal-close">✖</span>

    <h3>Novo Cargo</h3>

    <label class="form-label">Nome do cargo:</label>
    <input type="text" id="novoCargo" class="form-control" placeholder="Ex: Analista de TI">

    <label class="form-label mt-3">Descrição:</label>
    <textarea id="descricaoCargo" class="form-control" placeholder="Descreva o cargo (opcional)" rows="3"></textarea>

    <div class="d-flex gap-2 mt-3">
      <button class="btn btn-success" onclick="salvarCargo()">Salvar</button>
      <button class="btn btn-secondary" onclick="fecharModal('modalCargo')">Cancelar</button>
    </div>
  </div>
</div>

<!-- ===============================
     MODAL NOVO SETOR
=============================== -->
<div id="modalSetor" class="modal-custom">
  <div class="modal-custom-content">
    <span class="modal-close">✖</span>

    <h3>Novo Setor</h3>

    <label class="form-label">Nome do setor:</label>
    <input type="text" id="novoSetor" class="form-control" placeholder="Digite o nome do setor">

    <div class="d-flex gap-2 mt-3">
      <button class="btn btn-success" onclick="salvarSetor()">Salvar</button>
      <button class="btn btn-secondary" onclick="fecharModal('modalSetor')">Cancelar</button>
    </div>
  </div>
</div>

<?php
$conteudo = ob_get_clean();

$scripts = "
<script>
const mapaCargoSetor = " . json_encode($mapaCargoSetor) . ";
const setorGeral = $setorGeral;
</script>
<script src='/js/funcionarios_adicionar.js'></script>
";

include ROOT_PATH . '/includes/layout.php';
?>
