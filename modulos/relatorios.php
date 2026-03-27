<?php
session_start();
if (!isset($_SESSION['usuario']) || ($_SESSION['perfil'] ?? '') !== 'admin') {
  header('Location: ../index.php');
  exit;
}

$usuario = $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>📊 Relatórios</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .relatorio-menu {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-top: 30px;
    }
    .relatorio-card {
      background: #f4f4f4;
      border-radius: 8px;
      padding: 20px;
      text-align: center;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      transition: 0.3s;
    }
    .relatorio-card:hover {
      background: #eaeaea;
      transform: scale(1.02);
    }
    .relatorio-card h3 {
      margin-bottom: 10px;
    }
    .relatorio-card p {
      font-size: 14px;
      color: #555;
    }
    .relatorio-card a {
      display: inline-block;
      margin-top: 12px;
      padding: 8px 16px;
      background: #007bff;
      color: white;
      border-radius: 4px;
      text-decoration: none;
    }
    .relatorio-card a:hover {
      background: #0056b3;
    }
  </style>
</head>
<body>

<h2>📊 Relatórios Gerenciais</h2>
<p>Escolha uma categoria para visualizar os dados com filtros e exportações.</p>

<div class="relatorio-menu">

<div class="relatorio-card">
  <h3>🏬 Lojas</h3>
  <p>Relatório completo por loja: chamados, inconformidades, inventário e equipe. Ideal para visão gerencial.</p>
  <a href="relatorio_lojas.php">Acessar</a>
</div>


  <div class="relatorio-card">
    <h3>📨 Chamados</h3>
    <p>Visualize chamados por setor, status e loja. Gere relatórios com tempo médio e exportações.</p>
    <a href="relatorio_chamados.php">Acessar</a>
  </div>

  <div class="relatorio-card">
    <h3>🛠️ Inconformidades</h3>
    <p>Relatórios por loja, status e solução aplicada. Ideal para auditoria e acompanhamento.</p>
    <a href="relatorio_inconformidades.php">Acessar</a>
  </div>

  <div class="relatorio-card">
    <h3>📦 Inventário</h3>
    <p>Visualize equipamentos por loja, tipo e status. Gere relatórios de ativos e inativos.</p>
    <a href="relatorio_inventario.php">Acessar</a>
  </div>

  <div class="relatorio-card">
    <h3>👥 Funcionários</h3>
    <p>Relatórios por cargo, loja, status e tempo de empresa. Ideal para RH e gestão de pessoas.</p>
    <a href="relatorio_funcionarios.php">Acessar</a>
  </div>

  </div>

  <a class="btn" href="../index.php" style="margin-top:20px; display:inline-block;">🔙 Voltar ao painel principal</a>

  </body>
  </html>