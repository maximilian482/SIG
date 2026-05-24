<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

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
$registradoPor = preg_replace('/\D/', '', $registro['registrado_por']);
if ($registradoPor !== $cpfLogado) {
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
    $cupom          = $_POST['cupom'];
    $vendedor       = $_POST['vendedor'];
    $lote           = $_POST['lote'];
    $quantidade     = intval($_POST['quantidade']);

    $stmt = $conn->prepare("
        UPDATE controlados
        SET data_venda = ?, codigo_produto = ?, produto = ?, cupom = ?, vendedor = ?, lote = ?, quantidade = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "ssssssii",
        $data,
        $codigoProduto,
        $produto,
        $cupom,
        $vendedor,
        $lote,
        $quantidade,
        $id
    );

    $stmt->execute();

    $_SESSION['flash'] = [
        'mensagem' => 'Registro atualizado com sucesso!',
        'tipo' => 'success'
    ];

    // SEM CONDIÇÃO: sempre volta para a tela de registros
    header("Location: controlados_registros.php?filial=$filial");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Editar Registro</title>
    <link rel="stylesheet" href="/css/controlados.css">
</head>
<body>

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

            <label>Número do Cupom:</label>
            <input type="text" name="cupom" value="<?= $registro['cupom'] ?>" required oninput="this.value=this.value.replace(/[^0-9]/g,'')">

            <label>Vendedor:</label>
            <input type="text" name="vendedor" value="<?= $registro['vendedor'] ?>" required>

            <label>Lote:</label>
            <input type="text" name="lote" value="<?= $registro['lote'] ?>" required>

            <label>Quantidade:</label>
            <input type="number" name="quantidade" min="1" value="<?= $registro['quantidade'] ?>" required>

            <button class="btn btn-novo">💾 Salvar Alterações</button>
            <a href="controlados_registros.php?filial=<?= $filial ?>" class="btn btn-cinza">Cancelar</a>

        </form>
    </div>

</div>

</body>
</html>
