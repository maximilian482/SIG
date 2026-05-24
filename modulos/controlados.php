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
$cpfLogado     = trim(preg_replace('/\D/', '', $_SESSION['cpf'])); // CPF sem máscara + trim

/* ============================================================
   1) BUSCA FILIAL E CARGO DO USUÁRIO
   ============================================================ */
$stmt = $conn->prepare("SELECT loja_id, cargo_id FROM funcionarios WHERE cpf = ?");
$stmt->bind_param("s", $_SESSION['cpf']);
$stmt->execute();
$dadosUser = $stmt->get_result()->fetch_assoc();

$filialUsuario = $dadosUser['loja_id'];
$cargoUsuario  = $dadosUser['cargo_id'];

// CEO = 8, SUPER = 19
$ehAdmin = in_array($cargoUsuario, [8, 19]);

/* ============================================================
   2) DEFINIÇÃO DA FILIAL SELECIONADA
   ============================================================ */
$filialSelecionada = $_GET['filial'] ?? '';

if (!$ehAdmin) {
    $filialSelecionada = $filialUsuario;
}

if (!$filialSelecionada) {
    $filialSelecionada = $filialUsuario;
}

/* ============================================================
   3) BUSCA NOME DA FILIAL ATUAL
   ============================================================ */
$stmt = $conn->prepare("SELECT nome FROM lojas WHERE id = ?");
$stmt->bind_param("i", $filialSelecionada);
$stmt->execute();
$nomeFilialAtual = $stmt->get_result()->fetch_assoc()['nome'];

/* ============================================================
   4) LISTA DE FILIAIS (somente para CEO/SUPER)
   ============================================================ */
$filiais = $conn->query("
    SELECT id, nome 
    FROM lojas 
    WHERE nome NOT IN ('CAV', 'ESCRITÓRIO', 'CD')
    ORDER BY nome ASC
");

ob_start();
?>

<link rel="stylesheet" href="/css/controlados.css">

<div class="controlados-container" data-cpf="<?= $cpfLogado ?>">

    <!-- CABEÇALHO -->
    <div class="header-controlados">

        <div class="titulo-filial">
            💊 Controle – <?= htmlspecialchars($nomeFilialAtual) ?>
        </div><br>

       <!-- SELETOR DE FILIAL -->
        <?php if ($ehAdmin): ?>
            <form method="GET">
                <div class="aviso-filial">
                    Selecione uma filial para visualizar os registros.
                </div>

                <select name="filial" onchange="this.form.submit()">
                    <!-- Placeholder -->
                    <option value="" <?= empty($filialSelecionada) ? 'selected disabled' : '' ?>>
                        Selecionar...
                    </option>

                    <!-- Filiais -->
                    <?php while ($f = $filiais->fetch_assoc()): ?>
                        <option value="<?= $f['id'] ?>" <?= (!empty($filialSelecionada) && $filialSelecionada == $f['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($f['nome']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        <?php endif; ?>


        <!-- BOTÕES -->
        <div class="botoes-topo">
            <a href="ferramentas.php" class="btn btn-cinza">⬅ Voltar</a>
            <a href="controlados_registros.php?filial=<?= $filialSelecionada ?>" class="btn">📄 Ver Registros</a>
            <button class="btn btn-novo" onclick="abrirModal()">➕ Novo Registro</button>
        </div>

    </div>



    <!-- MODAL DE NOVO REGISTRO -->
    <div id="modalRegistro" class="modal">
        <div class="modal-content">

            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3>Novo Registro</h3>
                <button onclick="fecharModal()" style="background:none; border:none; font-size:22px; cursor:pointer;">✖</button>
            </div>

            <form method="POST" action="controlados_salvar.php" class="form-padrao">

                <input type="hidden" name="filial" value="<?= $filialSelecionada ?>">

                <label>Data da Venda:</label>
                <input type="date" name="data_venda" required>

                <label>Código do Produto:</label>
                <input type="text" name="codigo_produto" required oninput="this.value=this.value.replace(/[^0-9]/g,'')">

                <label>Nome do Produto:</label>
                <input type="text" name="produto" required>

                <label>Número do Cupom:</label>
                <input type="text" name="cupom" required oninput="this.value=this.value.replace(/[^0-9]/g,'')">

                <label>Vendedor:</label>
                <input type="text" name="vendedor" required>

                <label>Lote:</label>
                <input type="text" name="lote" required>

                <label>Quantidade:</label>
                <input type="number" name="quantidade" min="1" required>

                <button class="btn btn-novo">💾 Salvar</button>
                <button type="button" class="btn btn-cinza" onclick="fecharModal()">Cancelar</button>
            </form>

        </div>
    </div>

    <!-- LISTA DOS ÚLTIMOS 10 -->
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
                <th>Conf.</th>
                <th></th>

            </tr>

            <?php while ($r = $historico->fetch_assoc()): ?>

            <tr class="linha-registro">
                <td><?= htmlspecialchars($r['produto']) ?></td>
                <td><?= htmlspecialchars($r['vendedor']) ?></td>
                <td><?= $r['conferido'] ? '✔️' : '❌' ?></td>


                <td>
                    <button class="btn-toggle" onclick="toggleDetalhes(<?= $r['id'] ?>)">🔽</button>
                </td>
            </tr>

            <tr id="detalhes-<?= $r['id'] ?>" class="detalhes-linha">
            <td colspan="5">
                <div class="detalhes-box">

                    <p><strong>Data:</strong> <?= date('d/m/Y', strtotime($r['data_venda'])) ?></p>
                    <p><strong>Código:</strong> <?= htmlspecialchars($r['codigo_produto']) ?></p>
                    <p><strong>Cupom:</strong> <?= htmlspecialchars($r['cupom']) ?></p>
                    <p><strong>Registrado por:</strong> <?= htmlspecialchars($r['registrado_nome']) ?></p>
                    <p><strong>Vendedor:</strong> <?= htmlspecialchars($r['vendedor']) ?></p>
                    <p><strong>Produto:</strong> <?= htmlspecialchars($r['produto']) ?></p>
                    <p><strong>Lote:</strong> <?= htmlspecialchars($r['lote']) ?></p>
                    <p><strong>Quantidade:</strong> <?= $r['quantidade'] ?></p>

                    <p>
                        <strong>Conferido:</strong>
                        <?php if ($r['conferido']): ?>
                            <span style="color:#27ae60; font-weight:bold;">✔️ Sim</span>
                        <?php else: ?>
                            <span style="color:#e74c3c; font-weight:bold;">❌ Não</span>
                        <?php endif; ?>
                    </p>

                    <?php if ($r['conferido']): ?>
                        <p><strong>Conferido por:</strong> <?= htmlspecialchars($r['conferido_por']) ?></p>
                        <p><strong>Conferido em:</strong> <?= date('d/m/Y H:i', strtotime($r['conferido_em'])) ?></p>
                    <?php endif; ?>

                    <div class="acoes-detalhes">

                        <a href="controlados_editar.php?id=<?= $r['id'] ?>&filial=<?= $filialSelecionada ?>" 
                            class="btn-acao editar"
                            data-registrado="<?= trim($r['registrado_por']) ?>">
                            ✏️ Editar
                        </a>

                        <a href="controlados_excluir.php?id=<?= $r['id'] ?>&filial=<?= $filialSelecionada ?>"
                            class="btn-acao excluir"
                            data-registrado="<?= trim($r['registrado_por']) ?>">
                            🗑️ Excluir
                        </a>

                    </div>

                </div>
            </td>
</tr>


            <?php endwhile; ?>
        </table>
    </div>

</div>

<!-- JS SEPARADO -->
<script src="/js/controlados.js?v=<?= time() ?>"></script>
<?php if (isset($_GET['ok'])): ?>
<script>
    mostrarMensagem("Registro criado com sucesso!", "sucesso");
</script>
<?php endif; ?>



<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
