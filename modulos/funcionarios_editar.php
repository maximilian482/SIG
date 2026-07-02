<?php
session_start();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';
require_once ROOT_PATH . '/dados/conexao.php';

$conn = conectar();

// ===============================
// CONFIGURAÇÕES DO LAYOUT
// ===============================
$titulo = "Editar Funcionário";
$cssExtra = "/css/funcionarios.css";

// ===============================
// VALIDAR PARÂMETROS
// ===============================
$id   = intval($_GET['id'] ?? 0);
$loja = intval($_GET['loja'] ?? 0);

if ($id <= 0 || $loja <= 0) {
    $_SESSION['flash'] = [
        'mensagem' => '❌ Parâmetros inválidos.',
        'tipo' => 'erro'
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
  LEFT JOIN lojas l ON f.loja_id = l.id
  LEFT JOIN cargos c ON f.cargo_id = c.id
  WHERE f.id = ? AND f.loja_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $loja);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['flash'] = [
        'mensagem' => '❌ Funcionário não encontrado.',
        'tipo' => 'erro'
    ];
    header("Location: funcionarios.php");
    exit;
}

$f = $result->fetch_assoc();

// ===============================
// CARREGAR CARGOS
// ===============================
$cargos = [];
$resCargos = $conn->query("SELECT id, nome_cargo FROM cargos ORDER BY nome_cargo");
while ($row = $resCargos->fetch_assoc()) {
    $cargos[$row['id']] = $row['nome_cargo'];
}

// ===============================
// CARREGAR FUNÇÕES SECUNDÁRIAS
// ===============================
$funcoesSec = [];
$resFuncoes = $conn->query("SELECT id, nome FROM funcoes_secundarias ORDER BY nome");
while ($row = $resFuncoes->fetch_assoc()) {
    $funcoesSec[$row['id']] = $row['nome'];
}

// Buscar função secundária atual
$funcaoAtual = 0;
$resFuncaoAtual = $conn->query("
    SELECT funcao_secundaria_id 
    FROM funcionario_funcoes_secundarias 
    WHERE funcionario_id = {$f['id']} LIMIT 1
");
if ($resFuncaoAtual && $resFuncaoAtual->num_rows > 0) {
    $funcaoAtual = $resFuncaoAtual->fetch_assoc()['funcao_secundaria_id'];
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
// INICIAR CAPTURA DO HTML
// ===============================
ob_start();
?>

<div class="container">

    <h2 class="titulo-pagina">✏️ Editar Funcionário</h2>

    <!-- Removido bloco antigo de alertas -->
    <!-- Agora o layout.php exibe automaticamente via mostrarMensagem() -->

    <form method="POST" action="funcionarios_salvar_edicao.php" class="form-padrao">

        <input type="hidden" name="id" value="<?= $f['id'] ?>">
        <input type="hidden" name="loja_original" value="<?= $f['loja_id'] ?>">

        <label>Cód Vetor:</label>
        <input type="text" name="codigo" value="<?= htmlspecialchars($f['codigo']) ?>" required>

        <label>CC (Contabilidade):</label>
        <input type="text" name="cc" value="<?= htmlspecialchars($f['cc']) ?>" required>

        <label>Nome:</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($f['nome']) ?>" required>

        <label>Endereço:</label>
        <input type="text" name="endereco" value="<?= htmlspecialchars($f['endereco']) ?>">

        <label>CPF:</label>
        <input type="text" name="cpf" value="<?= htmlspecialchars($f['cpf']) ?>" required>

        <label>Cargo:</label>
        <select name="cargo_id" required>
            <option disabled>Selecione</option>
            <?php foreach ($cargos as $idCargo => $nomeCargo): ?>
                <option value="<?= $idCargo ?>" <?= $idCargo == $f['cargo_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($nomeCargo) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Loja:</label>
        <select name="loja_id" required>
            <option disabled>Selecione</option>
            <?php foreach ($lojas as $idLoja => $nomeLoja): ?>
                <option value="<?= $idLoja ?>" <?= $idLoja == $f['loja_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($nomeLoja) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Setor:</label>
        <select name="id_setor" required>
            <option disabled>Selecione</option>
            <?php foreach ($setores as $idSetor => $nomeSetor): ?>
                <option value="<?= $idSetor ?>" <?= $idSetor == $f['id_setor'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($nomeSetor) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Função Secundária:</label>
        <select name="funcao_secundaria_id">
            <option value="0">Nenhuma</option>
            <?php foreach ($funcoesSec as $idFunc => $nomeFunc): ?>
                <option value="<?= $idFunc ?>" <?= $idFunc == $funcaoAtual ? 'selected' : '' ?>>
                    <?= htmlspecialchars($nomeFunc) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <br><br>

        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($f['email']) ?>">

        <label>Data de contratação:</label>
        <input type="date" name="contratacao" value="<?= htmlspecialchars($f['contratacao']) ?>">

        <label>Data de nascimento:</label>
        <input type="date" name="aniversario" value="<?= htmlspecialchars($f['nascimento']) ?>">

        <label>Telefone:</label>
        <input type="text" name="telefone" value="<?= htmlspecialchars($f['telefone']) ?>">

        <button type="submit" class="btn btn-primary">💾 Salvar alterações</button>
        <a href="funcionarios.php" class="btn btn-secondary">❌ Cancelar</a>

    </form>

</div>

<?php
$cssExtra = "/css/funcionarios_editar.css";
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
