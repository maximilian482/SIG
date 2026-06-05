<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

if (!isset($_GET['id']) || !isset($_GET['filial'])) {
    header("Location: controlados_registros.php");
    exit;
}

$id     = intval($_GET['id']);
$filial = intval($_GET['filial']);
$cpfLogado = preg_replace('/\D/', '', $_SESSION['cpf']);

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
        'tipo' => 'error'
    ];
    header("Location: controlados_registros.php?filial=$filial");
    exit;
}

/* ============================
   BLOQUEIO DE EDIÇÃO
============================ */
$registradoBruto = trim($registro['registrado_por']);
$registradoCPF   = preg_replace('/\D/', '', $registradoBruto);

$nomeLogado      = trim($_SESSION['usuario']);
$primeiroNomeLogado = explode(' ', $nomeLogado)[0];

$autorizado = false;

if ($registradoCPF !== '' && $registradoCPF === $cpfLogado) {
    $autorizado = true;
}
elseif ($registradoCPF === '' && strcasecmp($registradoBruto, $primeiroNomeLogado) === 0) {
    $autorizado = true;
}

if (!$autorizado) {
    $_SESSION['flash'] = [
        'mensagem' => 'Somente o criador do protocolo pode editar.',
        'tipo' => 'error'
    ];
    header("Location: controlados_registros.php?filial=$filial");
    exit;
}

/* ============================
   SALVAR ALTERAÇÕES
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data           = $_POST['data_venda'];
    $codigoProduto  = $_POST['codigo_produto'];
    $produto        = $_POST['produto'];

    // Novo padrão: orçamento → cupom
    $orcamento      = trim($_POST['orcamento']);
    $cupom          = $orcamento;

    $vendedor       = $_POST['vendedor'];
    $lote           = $_POST['lote'];
    $quantidade     = intval($_POST['quantidade']);

    $observacao     = trim($_POST['observacao'] ?? '');

    $stmt = $conn->prepare("
        UPDATE controlados
        SET data_venda = ?, codigo_produto = ?, produto = ?, cupom = ?, vendedor = ?, lote = ?, quantidade = ?, observacao = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "ssssssisi",
        $data,
        $codigoProduto,
        $produto,
        $cupom,
        $vendedor,
        $lote,
        $quantidade,
        $observacao,
        $id
    );

    $stmt->execute();

    $_SESSION['flash'] = [
        'mensagem' => 'Registro atualizado com sucesso!',
        'tipo' => 'success'
    ];

    header("Location: controlados_registros.php?filial=$filial");
    exit;
}

ob_start();
?>

<div class="controlados-container">

    <h2>✏️ Editar Registro</h2>

    <div class="bloco">
        <form method="POST" class="form-padrao">

            <label>Data da Venda:</label>
            <input type="date" name="data_venda" value="<?= $registro['data_venda'] ?>" required>

            <label>Código do Produto:</label>
            <input type="text" name="codigo_produto" value="<?= $registro['codigo_produto'] ?>" required oninput="this.value=this.value.replace(/[^0-9]/g,'')">

            <label>Nome do Produto:</label>
            <input type="text" name="produto" value="<?= $registro['produto'] ?>" required>

            <label>Número do Orçamento:</label>
            <input type="text" name="orcamento" value="<?= $registro['cupom'] ?>" required oninput="this.value=this.value.replace(/[^0-9]/g,'')">

            <label>Vendedor:</label>
            <input type="text" name="vendedor" value="<?= $registro['vendedor'] ?>" required>

            <label>Lote:</label>
            <input type="text" name="lote" value="<?= $registro['lote'] ?>" required>

            <label>Quantidade:</label>
            <input type="number" name="quantidade" min="1" value="<?= $registro['quantidade'] ?>" required>

            <label>Observação (opcional):</label>
            <textarea name="observacao" rows="3"><?= htmlspecialchars($registro['observacao']) ?></textarea>

            <button class="btn btn-novo">💾 Salvar Alterações</button>
            <a href="controlados_registros.php?filial=<?= $filial ?>" class="btn btn-cinza">Cancelar</a>

        </form>
    </div>

</div>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
