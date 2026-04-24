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

// Filtros
$protocolo = $_GET['protocolo'] ?? '';
$descricao = $_GET['descricao'] ?? '';
$entrada = $_GET['entrada'] ?? ''; // loja solicitante
$saida = $_GET['saida'] ?? '';     // loja de liberação

// SQL base
$sql = "
    SELECT 
        ct.id,
        ct.protocolo,
        ct.descricao,
        lo.nome AS entrada_nome,
        ld.nome AS saida_nome,
        ct.status,
        ct.assinatura_nome,
        ct.assinatura_data
    FROM chamados_trilho ct
    LEFT JOIN lojas lo ON lo.id = ct.loja_origem_id
    LEFT JOIN lojas ld ON ld.id = ct.loja_destino_id
    WHERE 1 = 1
";

// Filtros dinâmicos
if ($protocolo !== '') {
    $sql .= " AND ct.protocolo LIKE '%" . $conn->real_escape_string($protocolo) . "%' ";
}

if ($descricao !== '') {
    $sql .= " AND ct.descricao LIKE '%" . $conn->real_escape_string($descricao) . "%' ";
}

if ($entrada !== '') {
    $sql .= " AND lo.id = " . intval($entrada);
}

if ($saida !== '') {
    $sql .= " AND ld.id = " . intval($saida);
}

$sql .= " ORDER BY ct.id DESC ";

$res = $conn->query($sql);

ob_start();
?>

<link rel="stylesheet" href="/css/trilho_historico.css">

<h2 class="titulo-historico">📁 Histórico do Trilho</h2>

<div class="trilho-acoes-topo">
    <a href="chamados_trilho.php" class="btn-trilho btn-voltar">⬅ Voltar ao Trilho</a>
    <a href="chamados_trilho_abrir.php" class="btn-trilho btn-novo">➕ Novo Protocolo</a>
</div>

<!-- FILTROS -->
<form method="GET" class="filtro-trilho">

    <div class="campo-filtro">
        <label>Protocolo</label>
        <input type="text" name="protocolo" value="<?= htmlspecialchars($protocolo) ?>">
    </div>

    <div class="campo-filtro">
        <label>Descrição</label>
        <input type="text" name="descricao" value="<?= htmlspecialchars($descricao) ?>">
    </div>

    <div class="campo-filtro">
        <label>Entrada</label>
        <select name="entrada">
            <option value="">Todas</option>
            <?php
            $lojas = $conn->query("SELECT id, nome FROM lojas ORDER BY nome");
            while ($l = $lojas->fetch_assoc()):
            ?>
                <option value="<?= $l['id'] ?>" <?= ($entrada == $l['id'] ? 'selected' : '') ?>>
                    <?= $l['nome'] ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="campo-filtro">
        <label>Saída</label>
        <select name="saida">
            <option value="">Todas</option>
            <?php
            $lojas2 = $conn->query("SELECT id, nome FROM lojas ORDER BY nome");
            while ($l2 = $lojas2->fetch_assoc()):
            ?>
                <option value="<?= $l2['id'] ?>" <?= ($saida == $l2['id'] ? 'selected' : '') ?>>
                    <?= $l2['nome'] ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="botoes-filtro">
        <button type="submit" class="btn-trilho btn-buscar">🔍 Buscar</button>
        <a href="trilho_historico.php" class="btn-trilho btn-limpar">🧹 Limpar</a>
    </div>

</form>

<hr>

<h3>Resultados</h3>

<?php if ($res->num_rows == 0): ?>
    <p>Nenhum registro encontrado.</p>
<?php else: ?>

<table class="tabela-historico">
    <thead>
        <tr>
            <th>Protocolo</th>
            <th>Descrição</th>
            <th>Entrada</th>
            <th>Saída</th>
            <th>Status</th>
            <th>Data Entrega</th>
            <th>Ações</th>
        </tr>
    </thead>

    <tbody>
        <?php while ($c = $res->fetch_assoc()): ?>
            <tr>
                <td><?= $c['protocolo'] ?></td>
                <td><?= $c['descricao'] ?></td>
                <td><?= $c['entrada_nome'] ?></td>
                <td><?= $c['saida_nome'] ?></td>
                <td><?= ucfirst($c['status']) ?></td>
                <td>
                    <?= $c['assinatura_data'] 
                        ? date('d/m/Y H:i', strtotime($c['assinatura_data'])) 
                        : '-' ?>
                </td>
                <td>
                    <button class="btn-trilho btn-detalhes" data-id="<?= $c['id'] ?>">Detalhes</button>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php endif; ?>

<!-- MODAL -->
<div id="modalDetalhes" class="modal-trilho">
    <div class="modal-conteudo">
        <span class="modal-fechar">&times;</span>
        <div id="modal-body-detalhes">Carregando...</div>
    </div>
</div>

<script>
// Modal
const modal = document.getElementById("modalDetalhes");
const modalBody = document.getElementById("modal-body-detalhes");
const fechar = document.querySelector(".modal-fechar");

fechar.addEventListener("click", () => modal.style.display = "none");
window.onclick = e => { if (e.target === modal) modal.style.display = "none"; };

document.querySelectorAll(".btn-detalhes").forEach(btn => {
    btn.addEventListener("click", () => {
        const id = btn.dataset.id;

        modal.style.display = "flex";
        modalBody.innerHTML = "Carregando...";

        fetch("chamados_trilho_detalhes.php?id=" + id)
            .then(r => r.text())
            .then(html => modalBody.innerHTML = html)
            .catch(() => modalBody.innerHTML = "<p style='color:red;'>Erro ao carregar detalhes.</p>");
    });
});
</script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
