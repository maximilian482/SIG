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

ob_start();
include ROOT_PATH . '/includes/flash.php';
?>

<link rel="stylesheet" href="/css/avaliacoes_historico.css">

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10 col-xxl-8">

            <a href="avaliacoes_loja.php" class="btn btn-outline-secondary mb-3">
                ⬅ Voltar
            </a>

            <h2 class="mb-2">📜 Histórico de Avaliações</h2>
            <p class="text-muted">Acompanhe o desempenho das lojas e aplique filtro por filial.</p>

            <!-- FILTRO POR FILIAL -->
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <label class="form-label">Filtrar por filial:</label>
                    <select id="filtro-filial" class="form-select">
                        <option value="">Todas</option>
                    </select>
                </div>
            </div>

            <!-- TABELA -->
            <div class="card shadow-sm historico-card">
                <div class="card-header historico-card-header">
                    Histórico
                </div>

                <div class="card-body">

                    <div class="table-responsive">
                        <table class="table table-hover align-middle historico-table">
                            <thead>
                                <tr>
                                    <th>Loja</th>
                                    <th>Nota geral</th>
                                    <th>Data</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-historico">
                                <!-- preenchido via JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINAÇÃO -->
                    <nav>
                        <ul class="pagination justify-content-center mt-3" id="paginacao"></ul>
                    </nav>

                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/js/avaliacoes_loja_historico.js?v=<?= time() ?>"></script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
