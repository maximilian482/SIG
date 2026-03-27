<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
include ROOT_PATH . '/includes/funcoes.php';

// ===============================
// CONFIGURAÇÕES DO LAYOUT
// ===============================
$titulo   = "Gestão de Setores";
$cssExtra = "/css/funcionarios_gestao_setores.css";

// ===============================
// FILTRO DE BUSCA
// ===============================
$busca = trim($_GET['busca'] ?? '');

$sqlBase = "SELECT id, nome FROM setores WHERE nome <> 'GERAL'";

if ($busca !== '') {
    $sql = $sqlBase . " AND nome LIKE ? ORDER BY nome";
    $stmt = $conn->prepare($sql);
    $like = "%$busca%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $setores = $stmt->get_result();
} else {
    $sql = $sqlBase . " ORDER BY nome";
    $setores = $conn->query($sql);
}

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

<?php if (!empty($_SESSION['erros'])): ?>
<script>
    mostrarMensagem("<?= addslashes(implode(' | ', $_SESSION['erros'])) ?>", "erro");
</script>
<?php unset($_SESSION['erros']); ?>
<?php endif; ?>

<h2>🏢 Gestão de Setores</h2>

<!-- Filtro -->
<form method="GET" class="filtro-form">
    <label>Buscar setor:</label>
    <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Digite o nome do setor">
    <button class="btn btn-small">🔍 Buscar</button>
    <a href="funcionarios_gestao_setores.php" class="btn btn-small btn-secondary">Limpar</a>
</form>

<!-- Botão novo setor -->
<button class="btn btn-small" onclick="abrirModalNovoSetor()">➕ Novo Setor</button>

<br><br>

<table class="tabela">
    <tr>
        <th>Nome</th>
        <th>Ações</th>
    </tr>

    <?php while ($s = $setores->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($s['nome']) ?></td>
        <td>
            <button class="btn btn-small" onclick="editarSetor(<?= $s['id'] ?>)">✏️</button>
            <button class="btn btn-small btn-danger" onclick="excluirSetor(<?= $s['id'] ?>)">🗑️</button>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

<br>
<a class="btn btn-secondary" href="funcionarios_menu.php">🔙 Voltar</a>

<!-- Modal Editar -->
<div id="modalEditar" class="modal">
  <div class="modal-conteudo">
    <h3>Editar Setor</h3>

    <input type="hidden" id="editId">

    <label>Nome:</label>
    <input type="text" id="editNome">

    <button class="btn" onclick="salvarEdicaoSetor()">Salvar</button>
    <button class="btn btn-danger" onclick="fecharModalEditar()">Cancelar</button>
  </div>
</div>

<!-- Modal Novo Setor -->
<div id="modalNovo" class="modal">
  <div class="modal-conteudo">
    <h3>Novo Setor</h3>

    <label>Nome:</label>
    <input type="text" id="novoNome">

    <button class="btn" onclick="salvarNovoSetor()">Criar</button>
    <button class="btn btn-danger" onclick="fecharModalNovo()">Cancelar</button>
  </div>
</div>

<script>
// ===============================
// EDITAR SETOR
// ===============================
function editarSetor(id) {
    fetch("funcionarios_get_setor.php?id=" + id)
        .then(r => r.json())
        .then(data => {
            if (data.erro) {
                mostrarMensagem(data.erro, "erro");
                return;
            }

            document.getElementById("editId").value = data.id;
            document.getElementById("editNome").value = data.nome;
            document.getElementById("modalEditar").style.display = "flex";
        })
        .catch(() => mostrarMensagem("Erro ao carregar dados do setor.", "erro"));
}

function fecharModalEditar() {
    document.getElementById("modalEditar").style.display = "none";
}

function salvarEdicaoSetor() {
    const id = document.getElementById("editId").value;
    const nome = document.getElementById("editNome").value.trim();

    if (!nome) {
        mostrarMensagem("O nome do setor é obrigatório.", "aviso");
        return;
    }

    fetch("funcionarios_editar_setor.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "id=" + id + "&nome=" + encodeURIComponent(nome)
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) {
            mostrarMensagem(data.mensagem, "sucesso");
            setTimeout(() => window.location.reload(), 800);
        } else {
            mostrarMensagem(data.erro, "erro");
        }
    })
    .catch(() => mostrarMensagem("Erro ao salvar edição.", "erro"));
}

// ===============================
// EXCLUIR SETOR
// ===============================
function excluirSetor(id) {
    if (!confirm("Tem certeza que deseja excluir este setor?")) return;

    fetch("funcionarios_excluir_setor.php?id=" + id)
        .then(r => r.json())
        .then(data => {
            if (data.sucesso) {
                mostrarMensagem(data.mensagem, "sucesso");
                setTimeout(() => window.location.reload(), 800);
            } else {
                mostrarMensagem(data.erro, "erro");
            }
        })
        .catch(() => mostrarMensagem("Erro ao excluir setor.", "erro"));
}

// ===============================
// NOVO SETOR
// ===============================
function abrirModalNovoSetor() {
    document.getElementById("novoNome").value = "";
    document.getElementById("modalNovo").style.display = "flex";
}

function fecharModalNovo() {
    document.getElementById("modalNovo").style.display = "none";
}

function salvarNovoSetor() {
    const nome = document.getElementById("novoNome").value.trim();

    if (!nome) {
        mostrarMensagem("O nome do setor é obrigatório.", "aviso");
        return;
    }

    fetch("funcionarios_novo_setor.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "nome=" + encodeURIComponent(nome)
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) {
            mostrarMensagem(data.mensagem, "sucesso");
            setTimeout(() => window.location.reload(), 800);
        } else {
            mostrarMensagem(data.erro, "erro");
        }
    })
    .catch(() => mostrarMensagem("Erro ao criar setor.", "erro"));
}
</script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . "/includes/layout.php";
