<?php
session_start();
if (!isset($_SESSION['usuario']) || ($_SESSION['perfil'] ?? '') !== 'admin') {
  header('Location: ../index.php');
  exit;
}

$funcionarios = json_decode(@file_get_contents('../dados/funcionarios.json'), true) ?: [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>👥 Relatório de Funcionários</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .submenu { display: flex; gap: 20px; margin-top: 30px; flex-wrap: wrap; }
    .submenu a {
      padding: 10px 20px;
      background: #007bff;
      color: white;
      border-radius: 6px;
      text-decoration: none;
    }
    .submenu a:hover { background: #0056b3; }
  </style>
</head>
<body>

<h2>👥 Relatório de Funcionários</h2>
<p>Escolha uma categoria para visualizar os dados:</p>

<div class="submenu">
  <a href="relatorio_funcionarios_loja.php">Por Loja</a>
  <a href="relatorio_funcionarios_cargo.php">Por Cargo</a>
  <a href="relatorio_funcionarios_status.php">Por Status</a>
  <a href="exportar_funcionarios.php">📥 Exportar Dados</a>
</div>

<a href="relatorios.php" class="btn" style="margin-top:30px;">🔙 Voltar aos relatórios</a>

</body>
</html>
