<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

if (!isset($_SESSION['cpf'])) {
    header("Location: /login.php");
    exit;
}

$usuarioLogado = $_SESSION['usuario'];
$cpfLogado     = trim(preg_replace('/\D/', '', $_SESSION['cpf']));

$filialSelecionada = $_GET['filial'] ?? '';

if (!$filialSelecionada) {
    header("Location: controlados.php");
    exit;
}

// Data de hoje para preencher automaticamente
$dataHoje = date('Y-m-d');

ob_start();
?>

<link rel="stylesheet" href="/css/controlados.css">
<link rel="stylesheet" href="/css/controlados_novo.css?v=<?= time() ?>">


<div class="controlados-container novo-registro">


    <div class="header-controlados">
        <div class="titulo-filial">
            💊 Novo Registro – Filial <?= htmlspecialchars($filialSelecionada) ?>
        </div>

        <div class="botoes-topo">
            <a href="controlados.php?filial=<?= $filialSelecionada ?>" class="btn btn-cinza">⬅ Voltar</a>
        </div>
    </div>

    <div class="form-wrapper">
        <h3>Cadastrar Novo Registro</h3>

        <form method="POST" action="controlados_salvar.php" class="form-padrao">

            <input type="hidden" name="filial" value="<?= $filialSelecionada ?>">

            <label>Data da Venda:</label>
            <input type="date" name="data_venda" value="<?= $dataHoje ?>" required>

            <label>Código do Produto:</label>
            <input type="text" name="codigo_produto" required oninput="this.value=this.value.replace(/[^0-9]/g,'')">

            <label>Nome do Produto:</label>
            <input type="text" name="produto" required>

            <label>Número do Orçamento:</label>
            <input type="text" name="orcamento" required oninput="this.value=this.value.replace(/[^0-9]/g,'')">

            <label>Vendedor:</label>
            <input type="text" name="vendedor" required>

            <label>Lote:</label>
            <input type="text" name="lote" required>

            <label>Quantidade:</label>
            <input type="number" name="quantidade" min="1" required>

            <label>Observação (opcional):</label>
            <textarea name="observacao" rows="3" placeholder="Digite algo se necessário..."></textarea>

            <button class="btn btn-novo">💾 Salvar</button>
            <a href="controlados.php?filial=<?= $filialSelecionada ?>" class="btn btn-cinza">Cancelar</a>
        </form>
    </div>

</div>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
