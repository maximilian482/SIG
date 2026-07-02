<?php
session_start();
require_once '../dados/conexao.php';
require_once '../includes/funcoes.php';

$conn = conectar();

// Permissão
$cpf   = $_SESSION['cpf'] ?? '';
$cargo = strtolower($_SESSION['cargo'] ?? '');

$acessoTotal = in_array($cargo, ['super', 'ceo']);

if (!$acessoTotal && !temAcesso($conn, $cpf, "gestao_compras_externas")) {
    echo "<h2 class='text-center text-danger mt-4'>❌ Você não tem permissão para acessar este módulo.</h2>";
    exit;
}

// Buscar lojas
$lojas = $conn->query("SELECT id, nome FROM lojas ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);

ob_start();
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/compras_externas_gestao.css">

<div class="container py-4">

    <h1 class="mb-3">🛒 Gestão de Compras Externas</h1>
    <p class="text-muted">Gerencie todas as solicitações enviadas pelas lojas.</p>

    <!-- FILTROS -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select id="filtroStatus" class="form-select">
                        <option value="">Todos</option>
                        <option value="aberto">Aberto</option>
                        <option value="em_compra">Em Compra</option>
                        <option value="aguardando_documentos">Aguardando Documentos</option>
                        <option value="concluido">Concluído</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Loja</label>
                    <select id="filtroLoja" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach ($lojas as $l): ?>
                            <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Busca</label>
                    <input type="text" id="filtroBusca" class="form-control" placeholder="Produto ou solicitante...">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-success w-100" id="btnFiltrar">🔎 Filtrar</button>
                </div>

            </div>

        </div>
    </div>

    <!-- TABELA -->
    <div class="table-responsive shadow-sm rounded">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Loja</th>
                    <th>Produto</th>
                    <th>Solicitante</th>
                    <th>Status</th>
                    <th class="text-center">Ações</th>
                </tr>
            </thead>

            <tbody id="listaGestao">
                <!-- preenchido via AJAX -->
            </tbody>
        </table>
    </div>

    <!-- PAGINAÇÃO -->
    <nav class="mt-3">
        <ul class="pagination pagination-sm" id="paginacaoGestao"></ul>
    </nav>

    <p class="text-muted small" id="infoTotal"></p>

</div>

<script src="/js/compras_externas_gestao.js"></script>

<?php
$conteudo = ob_get_clean();
include '../includes/layout.php';
?>
