<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

require_once __DIR__ . '/../config/bootstrap.php';
include ROOT_PATH . '/includes/funcoes.php';

// Configuração de layout
$titulo   = "Gestão de Cargos";
$cssExtra = "/css/funcionarios_gestao_cargos.css";

// Filtro de busca
$busca = trim($_GET['busca'] ?? '');

// Consulta cargos (exceto SUPER e CEO)
$sqlBase = "SELECT id, nome_cargo, descricao 
            FROM cargos 
            WHERE nome_cargo NOT IN ('SUPER', 'CEO')";

if ($busca !== '') {
    $sql = $sqlBase . " AND nome_cargo LIKE ? ORDER BY nome_cargo";
    $stmt = $conn->prepare($sql);
    $like = "%$busca%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $cargos = $stmt->get_result();
} else {
    $sql = $sqlBase . " ORDER BY nome_cargo";
    $cargos = $conn->query($sql);
}

// Inicia captura de conteúdo
ob_start();
?>

<h2>📋 Gestão de Cargos</h2>

<!-- Filtro de busca -->
<form method="GET" class="filtro-form">
    <label>Buscar cargo:</label>
    <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Digite o nome do cargo">
    <button class="btn btn-small">🔍 Buscar</button>
    <a href="funcionarios_gestao_cargos.php" class="btn btn-small btn-secondary">Limpar</a>
</form>

<!-- Botão novo cargo -->
<button class="btn btn-small" onclick="abrirModalNovoCargo()">➕ Novo Cargo</button>

<br><br>

<table class="tabela">
    <tr>
        <th>Nome</th>
        <th>Descrição</th>
        <th>Ações</th>
    </tr>

    <?php while ($c = $cargos->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($c['nome_cargo']) ?></td>
        <td><?= htmlspecialchars($c['descricao']) ?></td>
        <td>
            <button class="btn btn-small" onclick="editarCargo(<?= $c['id'] ?>)">✏️</button>
            <button class="btn btn-small btn-danger" onclick="excluirCargo(<?= $c['id'] ?>)">🗑️</button>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

<br>
<a class="btn btn-secondary" href="funcionarios_menu.php">🔙 Voltar</a>

<!-- Modal Editar -->
<div id="modalEditar" class="modal">
  <div class="modal-conteudo">
    <h3>Editar Cargo</h3>

    <input type="hidden" id="editId">

    <label>Nome:</label>
    <input type="text" id="editNome">

    <label>Descrição:</label>
    <textarea id="editDescricao" rows="3"></textarea>

    <button class="btn" onclick="salvarEdicaoCargo()">Salvar</button>
    <button class="btn btn-danger" onclick="fecharModalEditar()">Cancelar</button>
  </div>
</div>

<!-- Modal Novo Cargo -->
<div id="modalNovo" class="modal">
  <div class="modal-conteudo">
    <h3>Novo Cargo</h3>

    <label>Nome:</label>
    <input type="text" id="novoNome">

    <label>Descrição:</label>
    <textarea id="novoDescricao" rows="3"></textarea>

    <button class="btn" onclick="salvarNovoCargo()">Criar</button>
    <button class="btn btn-danger" onclick="fecharModalNovo()">Cancelar</button>
  </div>
</div>

<script>

// ===============================
// EDITAR CARGO
// ===============================
function editarCargo(id) {
    fetch("funcionarios_get_cargo.php?id=" + id)
        .then(r => r.json())
        .then(data => {
            if (data.erro) {
                mostrarMensagem(data.erro, "erro");
                return;
            }

            document.getElementById("editId").value = data.id;
            document.getElementById("editNome").value = data.nome;
            document.getElementById("editDescricao").value = data.descricao;
            document.getElementById("modalEditar").style.display = "flex";
        })
        .catch(() => mostrarMensagem("Erro ao carregar dados do cargo.", "erro"));
}

function fecharModalEditar() {
    document.getElementById("modalEditar").style.display = "none";
}

function salvarEdicaoCargo() {
    const id = document.getElementById("editId").value;
    const nome = document.getElementById("editNome").value.trim();
    const descricao = document.getElementById("editDescricao").value.trim();

    if (!nome) {
        mostrarMensagem("O nome do cargo é obrigatório.", "aviso");
        return;
    }

    fetch("funcionarios_editar_cargo.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "id=" + id + "&nome=" + encodeURIComponent(nome) + "&descricao=" + encodeURIComponent(descricao)
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
// EXCLUIR CARGO
// ===============================
function excluirCargo(id) {
    if (!confirm("Tem certeza que deseja excluir este cargo?")) return;

    fetch("funcionarios_excluir_cargo.php?id=" + id)
        .then(r => r.json())
        .then(data => {
            if (data.sucesso) {
                mostrarMensagem(data.mensagem, "sucesso");
                setTimeout(() => window.location.reload(), 800);
            } else {
                mostrarMensagem(data.erro, "erro");
            }
        })
        .catch(() => mostrarMensagem("Erro ao excluir cargo.", "erro"));
}


// ===============================
// NOVO CARGO
// ===============================
function abrirModalNovoCargo() {
    document.getElementById("novoNome").value = "";
    document.getElementById("novoDescricao").value = "";
    document.getElementById("modalNovo").style.display = "flex";
}

function fecharModalNovo() {
    document.getElementById("modalNovo").style.display = "none";
}

function salvarNovoCargo() {
    const nome = document.getElementById("novoNome").value.trim();
    const descricao = document.getElementById("novoDescricao").value.trim();

    if (!nome) {
        mostrarMensagem("O nome do cargo é obrigatório.", "aviso");
        return;
    }

    fetch("funcionarios_novo_cargo.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "nome=" + encodeURIComponent(nome) + "&descricao=" + encodeURIComponent(descricao)
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
    .catch(() => mostrarMensagem("Erro ao criar cargo.", "erro"));
}

</script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . "/includes/layout.php";
