<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';

$cpf = $_SESSION['cpf'] ?? '';

if (!$cpf) {
    echo "<h2 class='text-center text-danger mt-4'>❌ Sessão expirada.</h2>";
    exit;
}

// Buscar dados do usuário
$stmt = $conn->prepare("SELECT id, nome, loja_id FROM funcionarios WHERE cpf = ?");
$stmt->bind_param("s", $cpf);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    echo "<h2 class='text-center text-danger mt-4'>❌ Usuário não encontrado.</h2>";
    exit;
}

ob_start();
?>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- CSS do módulo -->
<link rel="stylesheet" href="../css/compras_externas.css">

<div class="container">

    <h2 class="titulo-pagina mb-4">🛒 Nova Solicitação de Compra Externa</h2>

    <div class="card-form">

        <form id="formCompraExterna" class="form-chamado">

            <label for="produto">Produto desejado *</label>
            <input type="text" id="produto" name="produto" class="form-control" required>

            <label for="quantidade">Quantidade *</label>
            <input type="number" step="0.01" id="quantidade" name="quantidade" class="form-control" required>

            <label for="motivo">Motivo da compra</label>
            <textarea id="motivo" name="motivo" rows="3" class="form-control"></textarea>

            <label for="urgencia">Urgência</label>
            <select id="urgencia" name="urgencia" class="form-select">
                <option value="baixa">Baixa</option>
                <option value="media">Média</option>
                <option value="alta">Alta</option>
            </select>

            <div class="botoes-form">
                <button type="submit" class="btn-enviar">📨 Enviar Solicitação</button>
                <a href="compras_externas.php" class="btn-voltar">🔙 Voltar</a>
            </div>

        </form>

    </div>

</div>

<script>
document.getElementById('formCompraExterna').addEventListener('submit', function (e) {
    e.preventDefault();

    const produto    = document.getElementById('produto').value.trim();
    const quantidade = parseFloat(document.getElementById('quantidade').value);
    const motivo     = document.getElementById('motivo').value.trim();
    const urgencia   = document.getElementById('urgencia').value;

    fetch('compras_externas_salvar.php', {
        method: 'POST',
        body: JSON.stringify({
            produto,
            quantidade,
            motivo,
            urgencia
        })
    })
    .then(res => res.json())
    .then(ret => {
        if (ret.sucesso) {
            mostrarMensagem("Solicitação enviada com sucesso!", "sucesso");
            setTimeout(() => {
                window.location.href = "compras_externas.php";
            }, 1500);
        } else {
            mostrarMensagem(ret.erro || "Erro ao salvar solicitação.", "erro");
        }
    })
    .catch(() => {
        mostrarMensagem("Erro de comunicação com o servidor.", "erro");
    });
});
</script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
