<?php
session_start();
require_once '../dados/conexao.php';
$conn = conectar();

// Relatórios com JOIN
$sqlLojas = "SELECT l.nome AS nome_loja, COUNT(*) AS total
             FROM funcionarios f
             JOIN lojas l ON f.loja_id = l.id
             GROUP BY l.nome
             ORDER BY l.nome";
$relatorioLojas = $conn->query($sqlLojas);

$sqlCargos = "SELECT c.nome_cargo, COUNT(*) AS total
              FROM funcionarios f
              JOIN cargos c ON f.cargo_id = c.id
              GROUP BY c.nome_cargo
              ORDER BY c.nome_cargo";
$relatorioCargos = $conn->query($sqlCargos);

// Preparar dados para gráficos
$dadosLojas = [];
while ($row = $relatorioLojas->fetch_assoc()) {
    $dadosLojas[$row['nome_loja']] = $row['total'];
}

$dadosCargos = [];
while ($row = $relatorioCargos->fetch_assoc()) {
    $dadosCargos[$row['nome_cargo']] = $row['total'];
}

// Total geral
$totalFuncionarios = array_sum($dadosLojas);

// Buscar cargos
$cargos = $conn->query("SELECT id, nome_cargo FROM cargos ORDER BY id");

// Buscar lojas
$lojas = $conn->query("SELECT id, nome FROM lojas ORDER BY id");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Gestão de Funcionários</title>
  <link rel="stylesheet" href="../css/gestao_funcionarios.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <div class="gestao-container">
    <h2>⚙️ Gestão de Funcionários</h2>

    <p class="resumo-total">👥 Total de funcionários ativos: <strong><?= $totalFuncionarios ?></strong></p>

    <div class="acoes-gestao">
      <a href="importar_funcionarios.php" class="btn-gestao">📥 Importar Funcionários</a>
      <a href="exportar_funcionarios.php" class="btn-gestao">📤 Exportar Funcionários</a>
    </div>
    
    <!-- Botão que abre o modal -->
    <button class="btn-gestao" onclick="document.getElementById('modalTutorial').style.display='block'">
      📖 Como preencher o CSV
    </button>

    <!-- Modal -->
    <div id="modalTutorial" class="modal">
      <div class="modal-content">
        <span class="close" onclick="document.getElementById('modalTutorial').style.display='none'">&times;</span>
        <h3>📄 Tutorial de Preenchimento do CSV <a href="baixar_modelo.php" class="btn-gestao">📄 Baixar</a></h3> 
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

        <div style="text-align:center; margin-top:15px;">
          <br><a href="baixar_modelo.php" class="btn-gestao">📄 Baixar Modelo CSV</a>
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
      <a href="funcionarios.php" class="btn-gestao">🔙 Voltar</a>
    </div>
  </div>

  <script>
    const dadosLojas = <?= json_encode($dadosLojas) ?>;
    const dadosCargos = <?= json_encode($dadosCargos) ?>;

    // Gráfico de Pizza (por Loja)
    new Chart(document.getElementById('graficoLojas'), {
      type: 'pie',
      data: {
        labels: Object.keys(dadosLojas),
        datasets: [{
          data: Object.values(dadosLojas),
          backgroundColor: ['#1E513D','#28a745','#66bb6a','#a5d6a7','#c8e6c9']
        }]
      }
    });

    // Gráfico de Barras (por Cargo)
    new Chart(document.getElementById('graficoCargos'), {
      type: 'bar',
      data: {
        labels: Object.keys(dadosCargos),
        datasets: [{
          label: 'Total',
          data: Object.values(dadosCargos),
          backgroundColor: '#1E513D'
        }]
      },
      options: {
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  </script>
</body>
</html>
