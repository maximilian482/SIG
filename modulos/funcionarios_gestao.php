<?php
session_start();

require_once __DIR__ . '/../config/bootstrap.php';
require_once ROOT_PATH . '/includes/funcoes.php';
require_once ROOT_PATH . '/dados/conexao.php';

$conn = conectar();


// ===============================
// 1) CONSULTAS
// ===============================

// Funcionários por loja
$sqlLojas = "
    SELECT l.nome AS nome_loja, COUNT(*) AS total
    FROM funcionarios f
    JOIN lojas l ON f.loja_id = l.id
    WHERE f.eh_funcionario = 1 AND f.desligamento IS NULL
    GROUP BY l.nome
    ORDER BY l.nome
";
$relatorioLojas = $conn->query($sqlLojas);

// Funcionários por cargo
$sqlCargos = "
    SELECT c.nome_cargo, COUNT(*) AS total
    FROM funcionarios f
    JOIN cargos c ON f.cargo_id = c.id
    WHERE f.eh_funcionario = 1 AND f.desligamento IS NULL
    GROUP BY c.nome_cargo
    ORDER BY c.nome_cargo
";
$relatorioCargos = $conn->query($sqlCargos);

// Preparar dados
$dadosLojas = [];
while ($row = $relatorioLojas->fetch_assoc()) {
    $dadosLojas[$row['nome_loja']] = $row['total'];
}

$dadosCargos = [];
while ($row = $relatorioCargos->fetch_assoc()) {
    $dadosCargos[$row['nome_cargo']] = $row['total'];
}

$totalFuncionarios = array_sum($dadosLojas);

// Buscar cargos e lojas
$cargos = $conn->query("SELECT id, nome_cargo FROM cargos ORDER BY id");
$lojas  = $conn->query("SELECT id, nome FROM lojas ORDER BY id");

// ===============================
// 2) CONFIGURAÇÕES DO LAYOUT
// ===============================
$titulo = "Gestão de Funcionários";
$cssExtra = "/css/funcionarios_gestao.css"; // seu CSS específico

// ===============================
// 3) CONTEÚDO DA PÁGINA
// ===============================
ob_start();
?>

<div class="gestao-container">
    <h2>⚙️ Gestão de Funcionários</h2>

    <p class="resumo-total">
        👥 Total de funcionários ativos: <strong><?= $totalFuncionarios ?></strong>
    </p>

    <div class="acoes-gestao">
        <a href="importar_funcionarios.php" class="btn btn-secondary">📥 Importar Funcionários</a>
        <a href="exportar_funcionarios.php" class="btn btn-secondary">📤 Exportar Funcionários</a>
    </div>

    <button class="btn" onclick="abrirModal()">📖 Como preencher o CSV</button>

    <!-- Modal -->
    <div id="modalTutorial" class="modal">
        <div class="modal-conteudo">
            <span class="close" onclick="fecharModal()">&times;</span>

            <h3>
                📄 Tutorial de Preenchimento do CSV 
                <a href="baixar_modelo.php" class="btn btn-small">📄 Baixar</a>
            </h3>

            <p>Para importar funcionários corretamente, siga estas instruções:</p>

            <ol>
                <li>O arquivo deve estar em formato <strong>CSV</strong> com o cabeçalho:<br>
                    <code>codigo vetor,nome,cpf,cargo_id,loja_id,email,contratacao,nascimento</code>
                </li>
                <li><strong>CPF</strong>: deve ter 11 dígitos, sem pontos ou traços.</li>
                <li><strong>Datas</strong>: podem ser <code>YYYY-MM-DD</code> ou <code>DD/MM/YYYY</code>.</li>
                <li><strong>Email</strong>: deve ser válido, opcional.</li>
                <li><strong>cargo_id</strong>: use o código do cargo conforme tabela abaixo.</li>
                <li><strong>loja_id</strong>: use o código da loja conforme tabela abaixo.</li>
            </ol>

            <h4>📌 Códigos de Cargos</h4>
            <table>
                <tr><th>ID</th><th>Nome do Cargo</th></tr>
                <?php while($c = $cargos->fetch_assoc()): ?>
                    <tr><td><?= $c['id'] ?></td><td><?= htmlspecialchars($c['nome_cargo']) ?></td></tr>
                <?php endwhile; ?>
            </table>

            <h4>🏬 Códigos de Lojas</h4>
            <table>
                <tr><th>ID</th><th>Nome da Loja</th></tr>
                <?php while($l = $lojas->fetch_assoc()): ?>
                    <tr><td><?= $l['id'] ?></td><td><?= htmlspecialchars($l['nome']) ?></td></tr>
                <?php endwhile; ?>
            </table>

            <h4>📊 Exemplo de linha preenchida</h4>
            <pre>12458,João da Silva,12345678901,1,2,joao@empresa.com,2020-05-10,1990-03-15</pre>

            <div class="text-center mt-20">
                <a href="baixar_modelo.php" class="btn">📄 Baixar Modelo CSV</a>
            </div>
        </div>
    </div>

    <div class="relatorio-funcionarios">
        <h3>📊 Funcionários por Loja</h3>
        <canvas id="graficoLojas"></canvas>

        <h3>📌 Funcionários por Cargo</h3>
        <canvas id="graficoCargos"></canvas>
    </div>

    <div class="voltar">
        <a href="funcionarios_menu.php" class="btn btn-secondary">🔙 Voltar</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const dadosLojas = <?= json_encode($dadosLojas) ?>;
const dadosCargos = <?= json_encode($dadosCargos) ?>;

// Modal
const modal = document.getElementById("modalTutorial");

function abrirModal() { modal.style.display = "flex"; }
function fecharModal() { modal.style.display = "none"; }

window.addEventListener("click", e => { if (e.target === modal) fecharModal(); });
document.addEventListener("keydown", e => { if (e.key === "Escape") fecharModal(); });

// Gráficos
new Chart(document.getElementById("graficoLojas"), {
    type: "pie",
    data: {
        labels: Object.keys(dadosLojas),
        datasets: [{
            data: Object.values(dadosLojas),
            backgroundColor: ["#1E513D","#28a745","#66bb6a","#a5d6a7","#c8e6c9"]
        }]
    }
});

new Chart(document.getElementById("graficoCargos"), {
    type: "bar",
    data: {
        labels: Object.keys(dadosCargos),
        datasets: [{
            label: "Total",
            data: Object.values(dadosCargos),
            backgroundColor: "#1E513D"
        }]
    },
    options: { scales: { y: { beginAtZero: true } } }
});
</script>

<?php
$conteudo = ob_get_clean();

// Renderiza o layout
include ROOT_PATH . "/includes/layout.php";
