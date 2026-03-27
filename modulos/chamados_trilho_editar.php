<?php
session_start();
require_once '../includes/funcoes.php';
require_once __DIR__ . '/../config/bootstrap.php';

$conn = conectar();

// ===============================
// VERIFICA LOGIN
// ===============================
if (!isset($_SESSION['funcionario_id'])) {
    header('Location: ../login.php');
    exit;
}

$usuarioLogado = intval($_SESSION['funcionario_id']);

// ===============================
// VALIDAR ID
// ===============================
$idTrilho = intval($_GET['id'] ?? 0);

if ($idTrilho <= 0) {
    echo "ID inválido.";
    exit;
}

// ===============================
// BUSCAR DADOS DO PROTOCOLO
// ===============================
$sql = "
    SELECT 
        t.id,
        t.protocolo,
        t.descricao,
        t.observacoes,
        t.solicitante_id,
        t.solicitado_id,
        t.loja_origem_id,
        t.loja_destino_id,
        t.status
    FROM chamados_trilho t
    WHERE t.id = {$idTrilho}
";

$dados = $conn->query($sql)->fetch_assoc();

if (!$dados) {
    echo "Protocolo não encontrado.";
    exit;
}

// ===============================
// PERMISSÃO: SOLICITANTE OU SOLICITADO
// ===============================
$criador = intval($dados['solicitante_id']);
$destinatario = intval($dados['solicitado_id']);

if ($usuarioLogado !== $criador && $usuarioLogado !== $destinatario) {
    echo "<p style='color:red;'>Você não tem permissão para editar este protocolo.</p>";
    exit;
}

// ===============================
// BLOQUEAR EDIÇÃO SE NÃO ESTIVER ABERTO
// ===============================
if ($dados['status'] !== 'aberto') {
    echo "<p style='color:red;'>Este protocolo não pode mais ser editado.</p>";
    exit;
}

// ===============================
// BUSCAR TODAS AS LOJAS
// ===============================
$lojas = $conn->query("
    SELECT id, nome 
    FROM lojas 
    ORDER BY nome
");

// ===============================
// BUSCAR TODOS OS FUNCIONÁRIOS ATIVOS
// ===============================
$funcionarios = $conn->query("
    SELECT id, nome 
    FROM funcionarios 
    WHERE desligamento IS NULL 
       OR desligamento = '' 
       OR desligamento = '0000-00-00'
    ORDER BY nome
");

// ===============================
// BUSCAR ITENS DO PROTOCOLO
// ===============================
$itens = $conn->query("
    SELECT id, codigo, descricao, quantidade
    FROM trilho_itens
    WHERE trilho_id = {$idTrilho}
")->fetch_all(MYSQLI_ASSOC);

// ===============================
// SALVAR ALTERAÇÕES
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $observacao = trim($_POST['observacoes']);
    $novoSolicitado = intval($_POST['solicitado_id']);
    $novaLojaSolicitada = intval($_POST['loja_destino_id']);

    // ITENS ATUALIZADOS
    $itensForm = $_POST['itens'] ?? [];

    // Montar array para gerar título
    $itensArray = [];
    foreach ($itensForm as $i) {
        if (!empty($i['descricao'])) {
            $itensArray[] = [
                'descricao' => $i['descricao'],
                'quantidade' => intval($i['quantidade'])
            ];
        }
    }

    // Gerar título automático
    $titulo = gerarTituloTrilho($itensArray);

    // ===============================
    // ATUALIZAR REGISTRO DO TRILHO
    // ===============================
    $stmt = $conn->prepare("
        UPDATE chamados_trilho
        SET descricao = ?, observacoes = ?, solicitado_id = ?, loja_destino_id = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "ssiii",
        $titulo,
        $observacao,
        $novoSolicitado,
        $novaLojaSolicitada,
        $idTrilho
    );

    $stmt->execute();

    // ===============================
    // ATUALIZAR ITENS
    // ===============================
    $conn->query("DELETE FROM trilho_itens WHERE trilho_id = {$idTrilho}");

    foreach ($itensForm as $i) {
        if (empty($i['descricao'])) continue;

        $stmtItem = $conn->prepare("
            INSERT INTO trilho_itens (trilho_id, codigo, descricao, quantidade)
            VALUES (?, ?, ?, ?)
        ");

        $stmtItem->bind_param(
            "issi",
            $idTrilho,
            $i['codigo'],
            $i['descricao'],
            $i['quantidade']
        );

        $stmtItem->execute();
    }

    setFlash("success", "Protocolo atualizado com sucesso!");
    header("Location: chamados_trilho.php?aba=aberto");
    exit;
}

ob_start();
?>

<link rel="stylesheet" href="/css/chamados_trilho_editar.css">

<h2 class="titulo-editar">✏ Editar Protocolo</h2>

<?php if ($flash = getFlash()): ?>
    <div class="flash flash-<?= $flash['tipo'] ?>">
        <?= htmlspecialchars($flash['mensagem']) ?>
    </div>
<?php endif; ?>

<form method="POST" class="formulario-editar">

    <p><strong>Protocolo:</strong> <?= htmlspecialchars($dados['protocolo']) ?></p>

    <label>Loja solicitada</label>
    <select name="loja_destino_id" required>
        <?php while ($l = $lojas->fetch_assoc()): ?>
            <option value="<?= $l['id'] ?>" <?= ($l['id'] == $dados['loja_destino_id'] ? 'selected' : '') ?>>
                <?= htmlspecialchars($l['nome']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <h3>Itens do Protocolo</h3>

    <table id="tabela-itens" class="tabela-itens">
        <thead>
            <tr>
                <th>Código</th>
                <th>Descrição</th>
                <th>Qtd</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($itens as $i): ?>
                <tr>
                    <td><input type="text" name="itens[<?= $i['id'] ?>][codigo]" value="<?= htmlspecialchars($i['codigo']) ?>"></td>
                    <td><input type="text" name="itens[<?= $i['id'] ?>][descricao]" value="<?= htmlspecialchars($i['descricao']) ?>"></td>
                    <td><input type="number" name="itens[<?= $i['id'] ?>][quantidade]" value="<?= intval($i['quantidade']) ?>" min="1"></td>
                    <td><button type="button" class="btn-remover">🗑</button></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <button type="button" id="btn-add-item">➕ Adicionar Item</button>

    <label>Solicitado para</label>
    <select name="solicitado_id" required>
        <?php
        $nomeAtual = $conn->query("SELECT nome FROM funcionarios WHERE id = {$dados['solicitado_id']}")->fetch_assoc()['nome'] ?? 'Funcionário';
        ?>
        <option value="<?= $dados['solicitado_id'] ?>" selected><?= htmlspecialchars($nomeAtual) ?></option>

        <?php while ($f = $funcionarios->fetch_assoc()): ?>
            <?php if ($f['id'] != $dados['solicitado_id']): ?>
                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome']) ?></option>
            <?php endif; ?>
        <?php endwhile; ?>
    </select>

    <label>Observações</label>
    <textarea name="observacoes" rows="4"><?= htmlspecialchars($dados['observacoes']) ?></textarea>

    <div class="botoes-acoes">
        <button class="btn-salvar" type="submit">💾 Salvar</button>
        <a class="btn-cancelar" href="chamados_trilho.php?aba=aberto">🔙 Cancelar</a>
    </div>

</form>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const tabela = document.querySelector("#tabela-itens tbody");
    const btnAdd = document.getElementById("btn-add-item");

    btnAdd.addEventListener("click", () => {
        const idTemp = "novo_" + Date.now();

        const linha = document.createElement("tr");
        linha.innerHTML = `
            <td><input type="text" name="itens[${idTemp}][codigo]" value=""></td>
            <td><input type="text" name="itens[${idTemp}][descricao]" value=""></td>
            <td><input type="number" name="itens[${idTemp}][quantidade]" value="1" min="1"></td>
            <td><button type="button" class="btn-remover">🗑</button></td>
        `;
        tabela.appendChild(linha);
    });

    tabela.addEventListener("click", (e) => {
        if (e.target.classList.contains("btn-remover")) {
            e.target.closest("tr").remove();
        }
    });

});
</script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . "/includes/layout.php";
