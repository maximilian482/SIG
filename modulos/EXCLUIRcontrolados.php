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

$usuarioLogado = $_SESSION['usuario'];
$cpfLogado     = $_SESSION['cpf'];

// ===============================
// 1) SELEÇÃO DE FILIAL
// ===============================
$filialSelecionada = $_GET['filial'] ?? '';

$filiais = $conn->query("
    SELECT id, nome 
    FROM lojas 
    WHERE nome NOT IN ('CAV', 'ESCRITÓRIO', 'CD')
    ORDER BY nome ASC
");

ob_start();
?>
<?php if (isset($_GET['ok'])): ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
    mostrarMensagem("Registro salvo com sucesso!", "sucesso");
});
</script>
<?php endif; ?>

<link rel="stylesheet" href="/css/controlados.css">

<div class="controlados-container">

    <h2>💊 Controle de Medicamentos Controlados</h2>

    <!-- BOTÃO VOLTAR -->
    <a href="ferramentas.php" class="btn" style="background:#888;">⬅ Voltar</a>

    <!-- BOTÃO VER REGISTROS -->
    <?php if ($filialSelecionada): ?>
        <a href="controlados_registros.php?filial=<?= $filialSelecionada ?>" class="btn" style="margin-left:10px;">
            📄 Ver Registros
        </a>
    <?php endif; ?>

    <hr>

    <!-- SELEÇÃO DE FILIAL -->
    <div class="bloco">
        <h3>Selecione a Filial</h3>

        <form method="GET">
            <select name="filial" required>
                <option value="">Selecione...</option>
                <?php while ($f = $filiais->fetch_assoc()): ?>
                    <option value="<?= $f['id'] ?>" <?= $filialSelecionada == $f['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['nome']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <button class="btn">Continuar</button>
        </form>
    </div>

    <?php if ($filialSelecionada): ?>

    <!-- FORMULÁRIO DE REGISTRO -->
    <div class="bloco">
        <h3>Registrar Venda</h3>

        <form method="POST" action="controlados_salvar.php" class="form-padrao">

            <input type="hidden" name="filial" value="<?= $filialSelecionada ?>">
            <input type="hidden" name="registrado_por" value="<?= $cpfLogado ?>">

            <label>Data da Venda:</label>
            <input type="date" name="data_venda" required>

            <label>Vendedor:</label>
            <input type="text" name="vendedor" placeholder="Digite o nome do vendedor" required>

            <label>Nome do Produto:</label>
            <input type="text" name="produto" required>

            <label>Lote:</label>
            <input type="text" name="lote" required>

            <label>Quantidade:</label>
            <input type="number" name="quantidade" min="1" required>

            <button class="btn">💾 Registrar</button>
        </form>
    </div>

    <!-- HISTÓRICO DOS ÚLTIMOS 10 -->
    <?php
    $stmt = $conn->prepare("
        SELECT *
        FROM controlados
        WHERE filial_id = ?
        ORDER BY id DESC
        LIMIT 10
    ");
    $stmt->bind_param("i", $filialSelecionada);
    $stmt->execute();
    $historico = $stmt->get_result();
    ?>

    <div class="bloco">
        <h3>Últimos 10 Registros</h3>

        <table class="tabela-mobile">
            <tr>
                <th>Produto</th>
                <th>Vendedor</th>
                <th>Lote</th>
                <th>Qtd</th>
                <th></th>
            </tr>

            <?php while ($r = $historico->fetch_assoc()): ?>

            <!-- LINHA PRINCIPAL -->
            <tr class="linha-registro">
                <td><?= htmlspecialchars($r['produto']) ?></td>
                <td><?= htmlspecialchars($r['vendedor']) ?></td>
                <td><?= htmlspecialchars($r['lote']) ?></td>
                <td><?= $r['quantidade'] ?></td>

                <td>
                    <button class="btn-toggle" onclick="toggleDetalhes(<?= $r['id'] ?>)">🔽</button>
                </td>
            </tr>

            <!-- DROPDOWN DE DETALHES -->
            <tr id="detalhes-<?= $r['id'] ?>" class="detalhes-linha">
                <td colspan="5">
                    <div class="detalhes-box">

                        <p><strong>Data:</strong> <?= date('d/m/Y', strtotime($r['data_venda'])) ?></p>
                        <p><strong>Registrado por:</strong> <?= htmlspecialchars($r['registrado_por']) ?></p>
                        <p><strong>Vendedor:</strong> <?= htmlspecialchars($r['vendedor']) ?></p>
                        <p><strong>Produto:</strong> <?= htmlspecialchars($r['produto']) ?></p>
                        <p><strong>Lote:</strong> <?= htmlspecialchars($r['lote']) ?></p>
                        <p><strong>Quantidade:</strong> <?= $r['quantidade'] ?></p>

                        <div class="acoes-detalhes">
                            <a href="controlados_editar.php?id=<?= $r['id'] ?>&filial=<?= $filialSelecionada ?>" 
                            class="btn-acao editar">✏️ Editar</a>

                            <a href="controlados_excluir.php?id=<?= $r['id'] ?>&filial=<?= $filialSelecionada ?>" 
                            class="btn-acao excluir"
                            onclick="return confirm('Excluir este registro?')">🗑️ Excluir</a>
                        </div>

                    </div>
                </td>
            </tr>

            <?php endwhile; ?>
        </table>
    </div>

    <script>
    function toggleDetalhes(id) {
        const linha = document.getElementById("detalhes-" + id);
        linha.classList.toggle("show");
    }
    </script>

    <?php endif; ?>

</div>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
