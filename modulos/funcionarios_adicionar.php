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
// CARREGAR DADOS
// ===============================
$erros = $_SESSION['erros_funcionario'] ?? [];
$dados = $_SESSION['dados_funcionario'] ?? [];

// Carregar cargos
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

// Carregar lojas
$lojas = [];
$resLojas = $conn->query("SELECT id, nome FROM lojas ORDER BY nome");
while ($row = $resLojas->fetch_assoc()) {
    $lojas[$row['id']] = $row['nome'];
}

// Carregar setores
$setores = [];
$resSetores = $conn->query("SELECT id, nome FROM setores ORDER BY nome");
while ($row = $resSetores->fetch_assoc()) {
    $setores[$row['id']] = $row['nome'];
}

// Buscar ID da loja GERAL
$lojaGeral = $conn->query("SELECT id FROM lojas WHERE nome = 'GERAL' LIMIT 1")->fetch_assoc()['id'];

// Buscar ID do setor GERAL
$setorGeral = $conn->query("SELECT id FROM setores WHERE nome = 'GERAL' LIMIT 1")->fetch_assoc()['id'];

// ===============================
// MAPA CARGO → SETOR
// ===============================
$mapaCargoSetor = [
    1 => 16,  // Gerente → Geral
    2 => 1,   // TI → TI
    3 => 13,  // Manutenção → Manutenção
    4 => 14,  // Supervisão → Supervisão
    5 => 16,  // Departamento Pessoal → Geral
    6 => 3,   // Recursos Humanos → RH
    7 => 16,  // Motorista → Geral
    8 => 7,   // CEO → Diretoria
    9 => 16,  // Auxiliar Administrativo → Geral
    10 => 16, // Balconista → Geral
    11 => 16, // Perfumista → Geral
    12 => 16, // Operador de Caixa → Geral
    13 => 16, // Repositor → Geral
    14 => 15, // Comprador → Compras
    15 => 16, // Subgerente → Geral
    16 => 16, // Farmacêutico → Geral
    17 => 16, // Estoquista → Geral
    18 => 16, // Locutor → Geral
    19 => 7,  // SUPER → Diretoria
    30 => 16, // Estagiário → Geral
    31 => 2,  // Supervisor Financeiro → Financeiro
    32 => 6,  // Supervisor de Vendas → Vendas
    33 => 16, // Balconista Treinee → Geral
    34 => 8,  // Prevenção de Perdas → Prevenção
    46 => 12  // Contador → Contabilidade
];

unset($_SESSION['erros_funcionario'], $_SESSION['dados_funcionario']);

// ===============================
// INICIAR CAPTURA DO HTML
// ===============================
ob_start();
?>

<?php if (!empty($_SESSION['sucesso'])): ?>
<script>
    mostrarMensagem("<?= addslashes($_SESSION['sucesso']) ?>", "sucesso");
</script>
<?php unset($_SESSION['sucesso']); ?>
<?php endif; ?>

<?php if (!empty($erros)): ?>
<script>
    mostrarMensagem("<?= addslashes(implode(' | ', $erros)) ?>", "erro");
</script>
<?php endif; ?>

<h2>➕ Adicionar novo funcionário</h2>

<form method="POST" action="funcionarios_salvar.php" class="form-funcionario">

  <label>Código Manual (Cod Vetor):</label>
  <input type="text" name="codigo" value="<?= htmlspecialchars($dados['codigo'] ?? '0') ?>">

  <label>CC (Contabilidade):</label>
  <input type="text" name="cc" value="<?= htmlspecialchars($dados['cc'] ?? '0') ?>">

  <label>Nome:</label>
  <input type="text" name="nome" value="<?= htmlspecialchars($dados['nome'] ?? '') ?>" required>

  <label>Endereço:</label>
  <input type="text" name="endereco" value="<?= htmlspecialchars($dados['endereco'] ?? '') ?>">

  <label>CPF:</label>
  <input type="text" name="cpf" pattern="\d{11}" title="Digite os 11 números do CPF"
         value="<?= htmlspecialchars($dados['cpf'] ?? '') ?>" required>

  <label>Cargo:</label>
  <div class="linha-flex">
      <select name="cargo_id" id="cargo_id" required>
          <option value="" disabled selected>Selecione</option>
          <?php foreach ($cargos as $id => $cargo): ?>
              <option value="<?= $id ?>"><?= htmlspecialchars($cargo) ?></option>
          <?php endforeach; ?>
      </select>
      <button type="button" class="btn-add" onclick="abrirModalCargo()">+</button>
  </div>

  <label>Setor:</label>
  <div class="linha-flex">
      <select name="id_setor" id="id_setor" required>
    <option value="" disabled selected>Selecione um setor</option>
    <?php foreach ($setores as $id => $nome): ?>
        <option value="<?= $id ?>"><?= htmlspecialchars($nome) ?></option>
    <?php endforeach; ?>
</select>


      <button type="button" class="btn-add" onclick="abrirModalSetor()">+</button>
  </div>

  <label>Loja:</label>
  <select name="loja_id" required>
    <?php foreach ($lojas as $id => $nome): ?>
      <option value="<?= $id ?>" <?= $id == $lojaGeral ? 'selected' : '' ?>>
        <?= htmlspecialchars($nome) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <label>Email:</label>
  <input type="email" name="email" value="<?= htmlspecialchars($dados['email'] ?? '') ?>">

  <label>Data de contratação:</label>
  <input type="date" name="contratacao" value="<?= htmlspecialchars($dados['contratacao'] ?? '') ?>" required>

  <label>Aniversário:</label>
  <input type="date" name="aniversario" value="<?= htmlspecialchars($dados['aniversario'] ?? '') ?>">

  <label>Telefone:</label>
  <input type="text" name="telefone" placeholder="(99) 99999-9999"
         value="<?= htmlspecialchars($dados['telefone'] ?? '') ?>">

  <input type="hidden" name="ativo" value="1">
  <input type="hidden" name="eh_funcionario" value="1">

  <div class="botoes-form">
    <button type="submit" class="btn">💾 Salvar</button>
    <a class="btn-secondary" href="funcionarios.php">🔙 Voltar</a>
  </div>

</form>

<!-- Modal Novo Cargo -->
<div id="modalCargo" class="modal">
  <div class="modal-conteudo">
    <h3>Novo Cargo</h3>

    <label>Nome do cargo:</label>
    <input type="text" id="novoCargo" placeholder="Ex: Analista de TI">

    <label>Descrição:</label>
    <textarea id="descricaoCargo" placeholder="Descreva o cargo (opcional)" rows="3"></textarea>

    <div class="modal-botoes">
      <button class="btn" onclick="salvarCargo()">Salvar</button>
      <button class="btn-secondary" onclick="fecharModalCargo()">Cancelar</button>
    </div>
  </div>
</div>

<!-- Modal Novo Setor -->
<div id="modalSetor" class="modal">
  <div class="modal-conteudo">
    <h3>Novo Setor</h3>

    <input type="text" id="novoSetor" placeholder="Digite o nome do setor">

    <div class="modal-botoes">
      <button class="btn" onclick="salvarSetor()">Salvar</button>
      <button class="btn-secondary" onclick="fecharModalSetor()">Cancelar</button>
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
