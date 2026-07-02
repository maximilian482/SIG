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

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/compras_externas.css">

<div class="container">

    <div class="topo-chamados">
        <h2>🛒 Compras Externas</h2>

        <a href="compras_externas_solicitar.php" class="btn-abrir-chamado">
            ➕ Nova Solicitação
        </a>
    </div>

    <!-- FILTROS -->
    <div class="card mb-3 shadow-sm">
        <div class="card-body">

            <div class="row g-3 align-items-end">

                <div class="col-md-4">
                    <label for="filtroSolicitante" class="form-label">Solicitante</label>
                    <input type="text" id="filtroSolicitante" class="form-control" placeholder="Nome do solicitante">
                </div>

                <div class="col-md-3">
                    <label for="filtroStatus" class="form-label">Status</label>
                    <select id="filtroStatus" class="form-select">
                        <option value="todos">Todos</option>
                        <option value="aberto">Aberto</option>
                        <option value="concluido">Concluído</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <button class="btn-enviar" id="btnFiltrar">
                        🔍 Aplicar Filtros
                    </button>
                </div>

            </div>

        </div>
    </div>

    <div class="compras-container">

        <div class="table-responsive">
            <table class="table table-striped table-hover tabela-compras mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Loja</th>
                        <th>Produto</th>
                        <th>Solicitante</th>
                        <th>Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>

                <tbody id="listaCompras">
                    <!-- preenchido via JS -->
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <div id="infoTotal" class="text-muted"></div>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="paginacaoCompras">
                    <!-- preenchido via JS -->
                </ul>
            </nav>
        </div>

    </div>

</div>

<script>
function excluirSolicitacao(id) {

    if (!confirm("Tem certeza que deseja excluir esta solicitação?")) return;

    fetch("compras_externas_excluir.php", {
        method: "POST",
        body: JSON.stringify({ id })
    })
    .then(res => res.json())
    .then(ret => {

        if (ret.sucesso) {
            mostrarMensagem("Solicitação excluída com sucesso!", "sucesso");
            carregarCompras(); // recarrega lista
        } else {
            mostrarMensagem(ret.erro, "erro");
        }

    })
    .catch(() => {
        mostrarMensagem("Erro ao comunicar com o servidor.", "erro");
    });
}

function carregarCompras(pagina = 1) {

    const solicitante = document.getElementById('filtroSolicitante').value.trim();
    const status      = document.getElementById('filtroStatus').value;

    fetch("/ajax/compras_externas_listar.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            pagina,
            solicitante,
            status
        })
    })
    .then(r => r.json())
    .then(ret => {

        if (!ret.sucesso) {
            mostrarMensagem(ret.erro || "Erro ao carregar lista.", "erro");
            return;
        }

        const tbody = document.getElementById('listaCompras');
        tbody.innerHTML = "";

        if (ret.dados.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        Nenhuma solicitação encontrada.
                    </td>
                </tr>
            `;
        } else {
            ret.dados.forEach(c => {

                const statusClassMap = {
                    'aberto': 'status-aberto',
                    'em_compra': 'status-andamento',
                    'aguardando_documentos': 'status-pendente',
                    'concluido': 'status-concluido'
                };
                const statusClass = statusClassMap[c.status] || 'status-aberto';

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>#${c.id}</td>
                    <td>${c.loja}</td>
                    <td>${c.produto}</td>
                    <td>${c.solicitante}</td>
                    <td>
                        <span class="status ${statusClass}">
                            ${c.status.toUpperCase()}
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="compras_externas_detalhes.php?id=${c.id}" 
                           class="btn-acao btn-ver">
                            🔍
                        </a>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Info total
        document.getElementById('infoTotal').textContent =
            `Total: ${ret.total} registro(s) • Página ${ret.pagina} de ${ret.paginas}`;

        // Paginação
        const pag = document.getElementById('paginacaoCompras');
        pag.innerHTML = "";

        const criarItem = (label, page, disabled = false, active = false) => {
            const li = document.createElement('li');
            li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
            const a = document.createElement('button');
            a.type = 'button';
            a.className = 'page-link';
            a.textContent = label;
            if (!disabled && !active) {
                a.onclick = () => carregarCompras(page);
            }
            li.appendChild(a);
            return li;
        };

        // << e <
        pag.appendChild(criarItem('«', 1, ret.pagina === 1));
        pag.appendChild(criarItem('‹', ret.pagina - 1, ret.pagina === 1));

        // páginas centrais (simples)
        for (let p = 1; p <= ret.paginas; p++) {
            pag.appendChild(criarItem(p, p, false, p === ret.pagina));
        }

        // > e >>
        pag.appendChild(criarItem('›', ret.pagina + 1, ret.pagina === ret.paginas));
        pag.appendChild(criarItem('»', ret.paginas, ret.pagina === ret.paginas));

    })
    .catch(() => {
        mostrarMensagem("Erro ao comunicar com o servidor.", "erro");
    });
}

// eventos
document.getElementById('btnFiltrar').addEventListener('click', () => carregarCompras(1));
document.getElementById('filtroSolicitante').addEventListener('keyup', (e) => {
    if (e.key === 'Enter') carregarCompras(1);
});

// carregar inicial
carregarCompras(1);
</script>

<?php
$conteudo = ob_get_clean();
include ROOT_PATH . '/includes/layout.php';
?>
