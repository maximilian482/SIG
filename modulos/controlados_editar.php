<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

// Verifica login
if (!isset($_SESSION['cpf'])) {
    header("Location: /login.php");
    exit;
}

$cpfLogado = preg_replace('/\D/', '', $_SESSION['cpf']);

/* ============================
   VERIFICA PARÂMETROS
============================ */
if (!isset($_GET['id']) || !isset($_GET['filial'])) {
    $_SESSION['flash'] = [
        'mensagem' => 'Parâmetros inválidos.',
        'tipo' => 'erro'
    ];
    header("Location: controlados.php");
    exit;
}

$id     = intval($_GET['id']);
$filial = intval($_GET['filial']);

/* ============================
   BUSCA O REGISTRO
============================ */
$stmt = $conn->prepare("SELECT * FROM controlados WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$registro = $stmt->get_result()->fetch_assoc();

if (!$registro) {
    $_SESSION['flash'] = [
        'mensagem' => 'Registro não encontrado.',
        'tipo' => 'erro'
    ];
    header("Location: controlados.php?filial=$filial");
    exit;
}

/* ============================
   BLOQUEIO DE EDIÇÃO
============================ */
$registradoBruto = trim($registro['registrado_por']);
$registradoCPF   = preg_replace('/\D/', '', $registradoBruto);

$nomeLogado         = trim($_SESSION['usuario']);
$primeiroNomeLogado = explode(' ', $nomeLogado)[0];

$autorizado = false;

// Caso novo: registrado_por é CPF
if ($registradoCPF !== '' && $registradoCPF === $cpfLogado) {
    $autorizado = true;
}
// Caso antigo: registrado_por é primeiro nome
elseif ($registradoCPF === '' && strcasecmp($registradoBruto, $primeiroNomeLogado) === 0) {
    $autorizado = true;
}

if (!$autorizado) {
    $_SESSION['flash'] = [
        'mensagem' => 'Somente o criador do protocolo pode editar.',
        'tipo' => 'erro'
    ];
    header("Location: controlados.php?filial=$filial");
    exit;
}

/* ============================
   SALVAR ALTERAÇÕES
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data           = $_POST['data_venda'];
    $codigoProduto  = $_POST['codigo_produto'];
    $produto        = $_POST['produto'];

    // Campo correto: orcamento
    $orcamento      = trim($_POST['orcamento']);

    $vendedor       = $_POST['vendedor'];
    $lote           = $_POST['lote'];
    $quantidade     = intval($_POST['quantidade']);
    $observacao     = trim($_POST['observacao'] ?? '');

    $stmt = $conn->prepare("
        UPDATE controlados
        SET data_venda = ?, codigo_produto = ?, produto = ?, orcamento = ?, vendedor = ?, lote = ?, quantidade = ?, observacao = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "ssssssisi",
        $data,
        $codigoProduto,
        $produto,
        $orcamento,
        $vendedor,
        $lote,
        $quantidade,
        $observacao,
        $id
    );

    $stmt->execute();

    $_SESSION['flash'] = [
        'mensagem' => 'Registro atualizado com sucesso!',
        'tipo' => 'sucesso'
    ];

    $origem = $_POST['origem'] ?? ($_GET['origem'] ?? '');

    if ($origem === 'registros') {
        header("Location: controlados_registros.php?filial=$filial");
    } else {
        header("Location: controlados.php?filial=$filial");
    }
    exit;
}

ob_start();
?>

<link rel="stylesheet" href="/css/controlados.css">
<link rel="stylesheet" href="/css/controlados_novo.css?v=<?= time() ?>">

<div class="controlados-container novo-registro">

    <div class="header-controlados">
        <div class="titulo-filial">
            ✏️ Editar Registro – Filial <?= htmlspecialchars($filial) ?>
        </div>

        <div class="botoes-topo">
            <?php $origem = $_GET['origem'] ?? ''; ?>
            <?php if ($origem === 'registros'): ?>
                <a href="controlados_registros.php?filial=<?= $filial ?>" class="btn btn-cinza">⬅ Voltar</a>
            <?php else: ?>
                <a href="controlados.php?filial=<?= $filial ?>" class="btn btn-cinza">⬅ Voltar</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-wrapper">
        <h3>Editar Registro</h3>

        <form method="POST" class="form-padrao">

            <input type="hidden" name="origem" value="<?= htmlspecialchars($_GET['origem'] ?? '') ?>">

            <label>Data da Venda:</label>
            <input type="date" name="data_venda" value="<?= $registro['data_venda'] ?>" required>

            <label>Código do Produto:</label>
            <input type="text" name="codigo_produto" value="<?= $registro['codigo_produto'] ?>" required oninput="this.value=this.value.replace(/[^0-9]/g,'')">

            <label>Nome do Produto:</label>
            <input type="text" name="produto" value="<?= $registro['produto'] ?>" required>

            <label>Número do Orçamento:</label>
            <input type="text" name="orcamento" value="<?= $registro['orcamento'] ?>" required oninput="this.value=this.value.replace(/[^0-9]/g,'')">

            <label>Vendedor:</label>
            <input type="text" name="vendedor" value="<?= $registro['vendedor'] ?>" required>

            <label>Lote:</label>
            <input type="text" name="lote" value="<?= $registro['lote'] ?>" required>

            <label>Quantidade:</label>
            <input type="number" name="quantidade" min="1" value="<?= $registro['quantidade'] ?>" required>

            <label>Observação (opcional):</label>
            <textarea name="observacao" rows="3"><?= htmlspecialchars($registro['observacao']) ?></textarea>

            <button class="btn btn-novo">💾 Salvar Alterações</button>

            <?php if ($origem === 'registros'): ?>
                <a href="controlados_registros.php?filial=<?= $filial ?>" class="btn btn-cinza">Cancelar</a>
            <?php else: ?>
                <a href="controlados.php?filial=<?= $filial ?>" class="btn btn-cinza">Cancelar</a>
            <?php endif; ?>

        </form>
    </div>

</div>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
